<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Create the Super Admin, or change its email/password if you're locked out.
 *
 *   php artisan store:admin
 */
class ManageAdmin extends Command
{
    protected $signature = 'store:admin';

    protected $description = 'Create the store admin account, or reset its email and password';

    public function handle(): int
    {
        $admin = Admin::query()->oldest('id')->first();

        $this->components->info($admin
            ? "Updating the admin account ({$admin->email})."
            : 'Creating the admin account.');

        $name = text('Name', default: $admin->name ?? 'Store Owner', required: true);
        $email = text('Email', default: $admin->email ?? '', required: true);
        $secret = password('New password (min. 8 characters)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $secret],
            ['name' => ['required', 'max:120'], 'email' => ['required', 'email'], 'password' => \App\Support\PasswordRules::console()],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $admin ??= new Admin;
        $admin->fill(['name' => $name, 'email' => $email, 'password' => $secret])->save();

        $this->components->info('Done. Sign in at '.url(config('store.admin_path').'/login'));

        return self::SUCCESS;
    }
}
