<?php

namespace App\Services\Maintenance;

use App\Models\Admin;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;

/**
 * The owner's choices for backups and clean-up (settings group "maintenance"), and the
 * scheduled runs that follow them.
 */
class MaintenanceOptions
{
    public const KEEP = [3, 7, 14, 30];
    public const SCHEDULES = ['off', 'daily', 'weekly', 'monthly'];

    public function __construct(private SettingsService $settings) {}

    /** @return array{auto: bool, keep: int, schedule: string, items: list<string>} */
    public function all(): array
    {
        $keep = (int) $this->settings->get('maintenance.backup_keep', 7);
        $schedule = (string) $this->settings->get('maintenance.cleanup_schedule', 'off');
        $items = $this->settings->get('maintenance.cleanup_items');

        return [
            'auto' => $this->settings->bool('maintenance.backup_auto', false),
            'keep' => in_array($keep, self::KEEP, true) ? $keep : 7,
            'schedule' => in_array($schedule, self::SCHEDULES, true) ? $schedule : 'off',
            'items' => is_array($items) ? array_values(array_intersect(Cleanup::ITEMS, $items)) : Cleanup::DEFAULT_ITEMS,
        ];
    }

    /** @param array<string, mixed> $values */
    public function save(array $values): void
    {
        $map = ['auto' => 'backup_auto', 'keep' => 'backup_keep', 'schedule' => 'cleanup_schedule', 'items' => 'cleanup_items'];
        $this->settings->setMany('maintenance', collect($values)->only(array_keys($map))->mapWithKeys(fn ($v, $k) => [$map[$k] => $v])->all());
    }

    public function lastRun(string $summary, ?Admin $admin): void
    {
        $this->settings->setMany('maintenance', ['cleanup_last_run' => [
            'at' => now()->toIso8601String(),
            'by' => $admin?->name,
            'summary' => $summary,
        ]]);
    }

    /** @return array{at: CarbonImmutable, by: string, summary: string}|null */
    public function last(): ?array
    {
        $run = $this->settings->get('maintenance.cleanup_last_run');
        if (! is_array($run) || empty($run['at'])) {
            return null;
        }

        return ['at' => CarbonImmutable::parse($run['at']), 'by' => $run['by'] ?: __('admin_backup.automatic_lower'), 'summary' => (string) ($run['summary'] ?? '')];
    }

    /**
     * Adds the nightly backup (03:00) and clean-up (03:30) to the scheduler. Each runs only
     * when switched on in the admin. Called from routes/console.php.
     */
    public static function register(?Schedule $schedule = null): void
    {
        $schedule ??= app(Schedule::class);
        $tz = config('store.display_timezone', 'UTC');

        $schedule->command('store:backup --auto')->dailyAt('03:00')->timezone($tz)
            ->withoutOverlapping()->name('store-backup')
            ->when(fn () => app(self::class)->all()['auto']);

        $schedule->command('store:cleanup --auto')->dailyAt('03:30')->timezone($tz)
            ->withoutOverlapping()->name('store-cleanup')
            ->when(fn () => app(self::class)->dueToday());
    }

    public function dueToday(?CarbonImmutable $today = null): bool
    {
        $today ??= CarbonImmutable::now(config('store.display_timezone', 'UTC'));

        return match ($this->all()['schedule']) {
            'daily' => true,
            'weekly' => $today->isSunday(),
            'monthly' => $today->day === 1,
            default => false,
        };
    }
}
