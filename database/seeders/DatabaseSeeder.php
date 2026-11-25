<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed
     *
     * Safe to run more than once: every seeder updates existing rows instead of duplicating them.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingsSeeder::class,
            CurrencySeeder::class,
            MenuSeeder::class,
            // A theme with its own design brings its own catalog and pages; otherwise the Demo Store demo.
            ...match (config('store.theme')) {
                'furnivo' => [FurnivoSeeder::class],
                'freshnest' => [FreshnestSeeder::class],
                default => [CatalogSeeder::class, ContentSeeder::class],
            },
            DemoCustomerSeeder::class,
        ]);
    }
}
