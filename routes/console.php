<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled jobs. On the server, one cron line runs them all:
// * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
\Illuminate\Support\Facades\Schedule::command('orders:release-unpaid')->everyTenMinutes()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('model:prune')->daily();
\Illuminate\Support\Facades\Schedule::command('auth:clear-resets')->daily();
\Illuminate\Support\Facades\Schedule::command('auth:clear-resets admins')->daily();

// Lets `php artisan store:launch-check` confirm the scheduler cron is running.
\Illuminate\Support\Facades\Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever('schedule:heartbeat', now()->toIso8601String()))
    ->everyMinute()->name('scheduler-heartbeat');
\Illuminate\Support\Facades\Schedule::command('carts:send-reminders')->everyFifteenMinutes()->withoutOverlapping();

// A public demo puts itself back every hour; on a real shop this never runs.
if (config('store.demo')) {
    \Illuminate\Support\Facades\Schedule::command('store:demo-reset --force')->hourly()->withoutOverlapping();
}
// People waiting for a piece are told once it is genuinely back.
\Illuminate\Support\Facades\Schedule::command('store:stock-alerts')->everyFifteenMinutes()->withoutOverlapping();

// Backup & clean-up (Settings → Backup) and the visitor map's free country database.
\App\Services\Maintenance\MaintenanceOptions::register();
\App\Console\Commands\GeoIpUpdate::schedule();
