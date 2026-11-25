<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Put the public demo back how it started.
 *
 * Rebuilds the database from the seeders and clears anything visitors uploaded. Meant for
 * cron on a demo site; it refuses to run unless DEMO_MODE=true, so it can never wipe a
 * real shop by accident.
 */
class DemoReset extends Command
{
    protected $signature = 'store:demo-reset {--force : Skip the confirmation}';

    protected $description = 'Reset the public demo to its starting state';

    public function handle(): int
    {
        if (! config('store.demo')) {
            $this->error('Refusing to run: this is not a demo site (DEMO_MODE is not true).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This wipes the database and uploaded files. Continue?')) {
            return self::SUCCESS;
        }

        $this->components->task('Clearing uploaded files', function () {
            $disk = Storage::disk(config('store.uploads.disk'));

            foreach (['settings', 'imports'] as $folder) {
                $disk->deleteDirectory($folder);
            }

            // Cleared before the seed, so the images the seeders create are kept.
            foreach (File::glob(storage_path('app/public/media/*')) as $path) {
                File::deleteDirectory($path);
            }

            return true;
        });

        $this->components->task('Rebuilding the database', function () {
            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

            return true;
        });

        $this->components->task('A year of orders, customers and sales', function () {
            return Artisan::call('store:demo-history', ['--force' => true]) === self::SUCCESS;
        });

        $this->components->task('Putting the demo images back', function () {
            // Furnivo: the design photos, from the local copy kept by the first download.
            Artisan::call(config('store.theme') === 'furnivo' ? 'store:import-design-images' : 'store:demo-images');

            return true;
        });

        // The sign-in advertised on the login page (DEMO_EMAIL / DEMO_PASSWORD), as its own
        // Manager account — the owner's login is never shown publicly.
        if (filled(config('store.demo_email')) && filled(config('store.demo_password'))) {
            $this->components->task('Demo sign-in account', fn () => Artisan::call('store:demo-admin') === self::SUCCESS);
        }

        Artisan::call('cache:clear');
        $this->info('The demo is back to its starting state.');

        return self::SUCCESS;
    }
}
