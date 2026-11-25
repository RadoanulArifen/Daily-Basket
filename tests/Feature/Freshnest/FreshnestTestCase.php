<?php

namespace Tests\Feature\Freshnest;

use Database\Seeders\CurrencySeeder;
use Database\Seeders\FreshnestSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/** Runs the FreshNest theme (resources/themes/freshnest) as a US-dollar, English store. */
abstract class FreshnestTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['store.theme' => 'freshnest', 'store.base_currency' => 'USD']);
        View::prependLocation(resource_path('themes/freshnest/views'));
        Storage::fake('public');
    }

    protected function seedFreshnest(): void
    {
        $this->seed([SettingsSeeder::class, CurrencySeeder::class, FreshnestSeeder::class]);
    }
}
