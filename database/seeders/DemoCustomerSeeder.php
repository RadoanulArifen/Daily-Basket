<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        User::firstOrCreate(['email' => 'customer@example.com'], [
            'name' => 'Mariam Al Hashimi',
            'phone' => '+971501234567',
            'locale' => 'en',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $this->seedPreviewCustomer();
    }

    public function seedPreviewCustomer(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        User::firstOrCreate(['email' => User::DEMO_EMAIL], [
            'name' => 'Demo Customer',
            'locale' => 'en',
            'password' => bin2hex(random_bytes(24)),
        ]);
    }
}
