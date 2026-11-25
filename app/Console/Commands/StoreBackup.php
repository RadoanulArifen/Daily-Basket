<?php

namespace App\Console\Commands;

use App\Services\Maintenance\DatabaseBackup;
use App\Services\Maintenance\MaintenanceOptions;
use Illuminate\Console\Command;

/** php artisan store:backup — a gzipped SQL backup in storage/app/private/backups. */
class StoreBackup extends Command
{
    protected $signature = 'store:backup
        {--auto : Made by the schedule (named "-auto")}
        {--keep= : Keep only this many backups afterwards (default: the admin setting)}';

    protected $description = 'Back up the database to storage/app/private/backups and remove old backups';

    public function handle(DatabaseBackup $backups, MaintenanceOptions $options): int
    {
        $backup = $backups->create();
        $this->info("Backup saved: storage/app/private/backups/{$backup->name} (".\Illuminate\Support\Number::fileSize($backup->size, 1).')');

        $keep = (int) ($this->option('keep') ?: $options->all()['keep']);
        $pruned = $backups->prune($keep);
        if ($pruned) {
            $this->line("Removed {$pruned} older backup(s); keeping the newest {$keep}.");
        }

        return self::SUCCESS;
    }
}
