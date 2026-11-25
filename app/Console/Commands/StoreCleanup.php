<?php

namespace App\Console\Commands;

use App\Services\Maintenance\Cleanup;
use App\Services\Maintenance\DatabaseBackup;
use App\Services\Maintenance\MaintenanceOptions;
use Illuminate\Console\Command;

/** php artisan store:cleanup — backs up, then removes the ticked old data. */
class StoreCleanup extends Command
{
    protected $signature = 'store:cleanup
        {--auto : Run by the schedule}
        {--items= : Comma-separated: carts,sessions,failed_jobs,statistics,drafts,media (default: the ticked ones in the admin)}
        {--dry-run : Only show what would be removed}';

    protected $description = 'Back up the database, then remove abandoned carts, expired sessions and other old data';

    public function handle(Cleanup $cleanup, MaintenanceOptions $options, DatabaseBackup $backups): int
    {
        $items = $this->option('items')
            ? array_values(array_intersect(Cleanup::ITEMS, array_map('trim', explode(',', $this->option('items')))))
            : $options->all()['items'];

        if ($items === []) {
            $this->warn('Nothing is ticked: choose items in Settings → Backup & clean-up, or pass --items.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $counts = $cleanup->counts();
            $this->table(['Item', 'Would remove'], collect($items)->map(fn ($i) => [$i, $counts[$i]])->all());

            return self::SUCCESS;
        }

        $result = $cleanup->run($items);
        $backups->prune($options->all()['keep']);
        $this->info($result['summary']);

        return self::SUCCESS;
    }
}
