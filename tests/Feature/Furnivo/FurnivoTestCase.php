<?php

namespace Tests\Feature\Furnivo;

use Database\Seeders\CurrencySeeder;
use Database\Seeders\FurnivoSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/** Runs the Furnivo theme (resources/themes/furnivo) as a US-dollar, English store. */
abstract class FurnivoTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['store.theme' => 'furnivo', 'store.base_currency' => 'USD']);
        View::prependLocation(resource_path('themes/furnivo/views'));
        Storage::fake('public');
    }

    protected function seedFurnivo(): void
    {
        $this->seed([SettingsSeeder::class, CurrencySeeder::class, FurnivoSeeder::class]);
    }
}
