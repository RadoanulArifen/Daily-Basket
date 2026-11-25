<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (Admin::query()->exists()) {
            $this->command?->info('Admin account already exists — skipped. Use "php artisan store:admin" to change it.');

            return;
        }

        $password = env('ADMIN_PASSWORD');

        // On a live install, a password nobody wrote down is worse than a clear failure.
        if (blank($password) && app()->environment('production')) {
            throw new \RuntimeException(
                'ADMIN_PASSWORD must be set in .env before seeding a production install. '
                .'Set it, then run: php artisan db:seed --force'
            );
        }

        $password = $password ?: \App\Support\PasswordGenerator::secure();

        Admin::create([
            'name' => env('ADMIN_NAME', 'Store Owner'),
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => $password,
            'locale' => 'en',
        ]);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn('Admin login: '.env('ADMIN_EMAIL', 'admin@example.com').'  password: '.$password);
            $this->command?->warn('Copy this password now — it will not be shown again.');
        }
    }
}
