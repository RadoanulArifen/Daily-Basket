<?php

namespace App\Services\Maintenance;

use App\Models\Admin;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;

/**
 * Database backups as gzipped SQL, written by PHP (no mysqldump needed on the server).
 *
 * Files live in storage/app/private/backups, which the web server never serves. They are
 * written to a ".part" file first and renamed when complete, so a half-written backup is
 * never listed. Rows of cache, sessions and queued jobs are left out (their tables are
 * kept, empty). Product images are not included.
 *
 * Restore: import the file with the hosting database tool (phpMyAdmin → Import, or
 * `gunzip < file.sql.gz | mysql …`). There is deliberately no restore button.
 */
class DatabaseBackup
{
    public const DIR = 'backups';

    /** The only names download and delete accept: no paths, no "../". */
    public const PATTERN = '/^backup-(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})-(auto|admin\d{1,10})\.sql\.gz$/';

    /** Tables whose rows are not worth keeping; the tables themselves are. */
    public const SKIP_ROWS = ['cache', 'cache_locks', 'sessions', 'jobs'];

    private const ROWS_PER_INSERT = 200;

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    /** Make a backup now. $admin null = made by the scheduler ("auto"). */
    public function create(?Admin $admin = null): BackupFile
    {
        @set_time_limit(600);
        $disk = $this->disk();
        $disk->makeDirectory(self::DIR);

        $when = CarbonImmutable::now('UTC');
        do {
            $name = 'backup-'.$when->format('Y-m-d-His').'-'.($admin ? 'admin'.$admin->getKey() : 'auto').'.sql.gz';
            $when = $when->addSecond();
        } while ($disk->exists(self::DIR.'/'.$name));

        $final = $disk->path(self::DIR.'/'.$name);
        $part = $final.'.part';

        $gz = gzopen($part, 'wb6');
        if ($gz === false) {
            throw new RuntimeException("Cannot write the backup file in storage/app/private/backups. Check the folder is writable.");
        }

        try {
            $this->dump(fn (string $sql) => gzwrite($gz, $sql));
        } catch (\Throwable $e) {
            gzclose($gz);
            @unlink($part);
            throw $e;
        }

        gzclose($gz);
        if (! @rename($part, $final)) {
            @unlink($part);
            throw new RuntimeException('Could not finish the backup file.');
        }

        return $this->info($name);
    }

    /** @return Collection<int, BackupFile> newest first */
    public function all(): Collection
    {
        return collect($this->disk()->files(self::DIR))
            ->map(fn (string $path) => basename($path))
            ->filter(fn (string $name) => self::valid($name))
            ->map(fn (string $name) => $this->info($name))
            ->sortByDesc(fn (BackupFile $b) => $b->date->getTimestamp().$b->name)
            ->values();
    }

    public static function valid(string $name): bool
    {
        return (bool) preg_match(self::PATTERN, $name);
    }

    /** Full path of a backup, or null when the name is not a backup that exists. */
    public function path(string $name): ?string
    {
        if (! self::valid($name) || ! $this->disk()->exists(self::DIR.'/'.$name)) {
            return null;
        }

        return $this->disk()->path(self::DIR.'/'.$name);
    }

    public function delete(string $name): bool
    {
        return $this->path($name) !== null && $this->disk()->delete(self::DIR.'/'.$name);
    }

    /** Keep the newest $keep backups; returns how many were deleted. */
    public function prune(int $keep): int
    {
        $old = $this->all()->slice(max(1, $keep));
        $old->each(fn (BackupFile $b) => $this->disk()->delete(self::DIR.'/'.$b->name));

        return $old->count();
    }

    public function info(string $name): BackupFile
    {
        preg_match(self::PATTERN, $name, $m);
        $date = CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4], (int) $m[5], (int) $m[6], 'UTC');
        $auto = $m[7] === 'auto';
        $by = $auto ? __('admin_backup.automatic') : (Admin::query()->find((int) substr($m[7], 5))?->name ?? 'Admin #'.substr($m[7], 5));

        return new BackupFile($name, $date, $by, (int) $this->disk()->size(self::DIR.'/'.$name), $auto);
    }

    /**
     * Writes the whole database as SQL statements through $write.
     *
     * @param  callable(string): mixed  $write
     */
    public function dump(callable $write): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();
        $mysql = in_array($driver, ['mysql', 'mariadb'], true);

        if (! $mysql && $driver !== 'sqlite') {
            throw new RuntimeException("Backups support MySQL, MariaDB and SQLite (this store uses {$driver}).");
        }

        $q = fn (string $id) => $mysql ? '`'.str_replace('`', '``', $id).'`' : '"'.str_replace('"', '""', $id).'"';

        $write('-- '.config('app.name').' database backup'."\n");
        $write('-- Made '.now()->toIso8601String()." by the store admin (App\\Services\\Maintenance\\DatabaseBackup)\n");
        $write("-- Restore: import this file with your hosting database tool (e.g. phpMyAdmin → Import).\n\n");
        $write($mysql ? "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n" : "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");

        foreach ($this->tables() as $table) {
            $write("-- Table {$table}\n");
            $write('DROP TABLE IF EXISTS '.$q($table).";\n");

            if ($mysql) {
                $create = (array) DB::selectOne('SHOW CREATE TABLE '.$q($table));
                $write(array_values($create)[1].";\n\n");
            } else {
                $create = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])->sql;
                $write($create.";\n");
                foreach (DB::select("SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql IS NOT NULL", [$table]) as $index) {
                    $write($index->sql.";\n");
                }
                $write("\n");
            }

            if (in_array($table, self::SKIP_ROWS, true)) {
                continue;
            }

            if ($mysql) {
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }

            try {
                $statement = $pdo->query('SELECT * FROM '.$q($table));
                $columns = null;
                $batch = [];

                while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns ??= '('.implode(', ', array_map($q, array_keys($row))).')';
                    $batch[] = '('.implode(', ', array_map(fn ($v) => $this->literal($pdo, $v), $row)).')';

                    if (count($batch) >= self::ROWS_PER_INSERT) {
                        $write('INSERT INTO '.$q($table).' '.$columns." VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }

                if ($batch) {
                    $write('INSERT INTO '.$q($table).' '.$columns." VALUES\n".implode(",\n", $batch).";\n");
                }
                $statement->closeCursor();
            } finally {
                if ($mysql) {
                    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                }
            }

            $write("\n");
        }

        $write($mysql ? "SET FOREIGN_KEY_CHECKS=1;\n" : "COMMIT;\nPRAGMA foreign_keys=ON;\n");
    }

    /** @return list<string> */
    private function tables(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
                ->pluck('name')->all();
        }

        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->sort()->values()->all();
    }

    private function literal(PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_float($value) => (string) $value,
            is_bool($value) => $value ? '1' : '0',
            default => $pdo->quote((string) $value),
        };
    }
}
