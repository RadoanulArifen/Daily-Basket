<?php

namespace App\Services\Maintenance;

use App\Models\Admin;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Settings → Backup & clean-up → "Clean now", and `php artisan store:cleanup`.
 *
 * Removes only data the store no longer needs. Orders, customers and products are never
 * touched. A backup is always made first.
 */
class Cleanup
{
    public const ITEMS = ['carts', 'sessions', 'failed_jobs', 'statistics', 'drafts', 'media'];

    /** Ticked when nothing has been saved yet (image folders are files, so they are opt-in). */
    public const DEFAULT_ITEMS = ['carts', 'sessions', 'failed_jobs', 'statistics', 'drafts'];

    /**
     * Statistics tables and their date column. Tables that don't exist yet (e.g. visitor
     * traffic before it is installed) are skipped.
     */
    public const STATISTICS = [
        'funnel_counts' => 'day',
        'search_misses' => 'last_searched_at',
        'traffic_visits' => 'started_at',
        'traffic_bot_counts' => 'day',
    ];

    public function __construct(private DatabaseBackup $backups) {}

    /** @return array<string, int> item => rows or folders that would go now */
    public function counts(): array
    {
        $counts = [];
        foreach (self::ITEMS as $item) {
            $counts[$item] = $item === 'media' ? count($this->unusedMediaFolders()) : (int) $this->queries($item)->sum(fn ($query) => $query->count());
        }

        return $counts;
    }

    /**
     * Back up, then delete the ticked items.
     *
     * @param  list<string>  $items
     * @return array{backup: string, removed: array<string, int>, summary: string}
     */
    public function run(array $items, ?Admin $admin = null): array
    {
        $items = array_values(array_intersect(self::ITEMS, $items));
        $backup = $this->backups->create($admin);

        $removed = [];
        foreach ($items as $item) {
            $removed[$item] = $item === 'media' ? $this->deleteMediaFolders() : (int) $this->queries($item)->sum(fn ($query) => $query->delete());
        }

        $summary = self::summarise($removed, $backup->name);

        AuditLog::record('deleted', 'cleanup', null, $summary, ['removed' => $removed, 'backup' => $backup->name]);
        app(MaintenanceOptions::class)->lastRun($summary, $admin);

        return ['backup' => $backup->name, 'removed' => $removed, 'summary' => $summary];
    }

    /** @param array<string, int> $removed */
    public static function summarise(array $removed, string $backup): string
    {
        $parts = collect($removed)
            ->filter()
            ->map(fn (int $n, string $item) => trans_choice("admin_backup.units.{$item}", $n, ['count' => number_format($n)]))
            ->values();

        return __('admin_backup.summary', [
            'backup' => $backup,
            'removed' => $parts->isEmpty() ? __('admin_backup.nothing_removed') : $parts->join(', ', ' '.__('admin_backup.and').' '),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, \Illuminate\Database\Query\Builder> */
    private function queries(string $item): \Illuminate\Support\Collection
    {
        $now = CarbonImmutable::now();
        $has = fn (string $table) => Schema::hasTable($table);

        $queries = match ($item) {
            // Guests only: a signed-in customer's cart is theirs to keep. Items go with the cart (cascade).
            'carts' => $has('carts') ? [DB::table('carts')->whereNull('user_id')->where('updated_at', '<', $now->subDays(30))] : [],
            'sessions' => $has('sessions') ? [DB::table('sessions')->where('last_activity', '<', $now->subMinutes((int) config('session.lifetime', 120))->getTimestamp())] : [],
            'failed_jobs' => $has('failed_jobs') ? [DB::table('failed_jobs')->where('failed_at', '<', $now->subDays(30))] : [],
            'statistics' => collect(self::STATISTICS)
                ->filter(fn ($column, $table) => $has($table) && Schema::hasColumn($table, $column))
                ->map(fn ($column, $table) => DB::table($table)->where($column, '<', $now->subYear()->toDateString()))
                ->values()->all(),
            'drafts' => $has('order_drafts') ? [DB::table('order_drafts')->where('updated_at', '<', $now->subDays(60))] : [],
            default => [],
        };

        return collect($queries);
    }

    /**
     * Upload folders (media/YYYY/MM/<uuid>) that no media-library row points to. Folders
     * changed in the last day are skipped: an upload may still be finishing.
     *
     * @return list<string>
     */
    public function unusedMediaFolders(): array
    {
        $diskName = config('store.uploads.disk', 'public');
        $disk = Storage::disk($diskName);
        if (! method_exists($disk, 'path') || config("filesystems.disks.{$diskName}.driver") !== 'local') {
            return [];   // only local disks can be checked safely
        }

        $known = Schema::hasTable('media') ? DB::table('media')->pluck('directory')->map(fn ($d) => trim((string) $d, '/'))->flip() : collect();
        $cutoff = time() - 86400;
        $unused = [];

        foreach ($disk->directories('media') as $year) {
            foreach ($disk->directories($year) as $month) {
                foreach ($disk->directories($month) as $folder) {
                    if (! preg_match('#^media/\d{4}/\d{2}/[0-9a-f-]{36}$#i', $folder) || $known->has($folder)) {
                        continue;
                    }
                    $path = $disk->path($folder);
                    $latest = @filemtime($path) ?: time();
                    foreach (glob($path.'/*') ?: [] as $file) {
                        $latest = max($latest, @filemtime($file) ?: 0);
                    }
                    if ($latest < $cutoff) {
                        $unused[] = $folder;
                    }
                }
            }
        }

        return $unused;
    }

    private function deleteMediaFolders(): int
    {
        $disk = Storage::disk(config('store.uploads.disk', 'public'));
        $folders = $this->unusedMediaFolders();
        foreach ($folders as $folder) {
            $disk->deleteDirectory($folder);
        }

        return count($folders);
    }
}
