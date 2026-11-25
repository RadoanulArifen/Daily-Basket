<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Console\Command;

/**
 * Create (or reset) the account the demo sign-in panel advertises.
 *
 * The panel on the admin sign-in page shows DEMO_EMAIL and DEMO_PASSWORD; this makes sure
 * an account with exactly those details exists. It is a separate account from the shop
 * owner's. Its role is DEMO_ROLE (default: owner, so visitors can open every screen). Nothing can
 * be changed in demo mode (DemoMode middleware refuses every write), saved keys and secrets are
 * never shown (not even their last characters) and other admins' emails are masked.
 *
 * Refuses to run unless DEMO_MODE=true, so it can never add a public account to a real shop.
 */
class DemoAdmin extends Command
{
    protected $signature = 'store:demo-admin';

    protected $description = 'Create the demo admin account shown on the sign-in page';

    public function handle(): int
    {
        if (! config('store.demo')) {
            $this->components->error('Refusing to run: this is not a demo site (DEMO_MODE is not true).');

            return self::FAILURE;
        }

        $email = (string) config('store.demo_email');
        $password = (string) config('store.demo_password');

        if ($email === '' || $password === '') {
            $this->components->error('Set DEMO_EMAIL and DEMO_PASSWORD in .env first.');

            return self::FAILURE;
        }

        if (str_contains(strtolower($password), 'changeme') || mb_strlen($password) < 8 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
            $this->components->error('DEMO_PASSWORD must be at least 8 characters with a letter and a number.');

            return self::FAILURE;
        }

        $admin = Admin::query()->firstOrNew(['email' => $email]);
        $existed = $admin->exists;

        $admin->fill([
            'name' => 'Demo',
            'password' => $password,
            'role' => AdminRole::tryFrom((string) config('store.demo_role', 'owner')) ?? AdminRole::Owner,
            'locale' => 'en',
            'is_active' => true,
        ])->save();

        $this->components->info(($existed ? 'Reset' : 'Created')." the demo admin: {$email}");
        $this->components->info('Sign in at '.url(config('store.admin_path').'/login'));

        return self::SUCCESS;
    }
}
