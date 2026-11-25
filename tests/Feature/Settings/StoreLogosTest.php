<?php

namespace Tests\Feature\Settings;

use App\Models\Admin;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class StoreLogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        Storage::fake(config('store.uploads.disk'));
        // The storefront checks below are about the Furnivo header.
        config(['store.theme' => 'furnivo']);
        View::prependLocation(resource_path('themes/furnivo/views'));
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    private function save(array $extra): void
    {
        $this->put('/admin/settings/general', array_merge([
            'store_name' => ['en' => 'Furnivo'],
            'store_email' => 'hello@furnivo.com',
        ], $extra))->assertSessionDoesntHaveErrors();
    }

    public function test_both_logos_can_be_uploaded(): void
    {
        $this->save([
            'logo' => UploadedFile::fake()->image('dark.png', 320, 90),
            'logo_white' => UploadedFile::fake()->image('white.png', 320, 90),
        ]);

        $settings = app(SettingsService::class);
        $dark = $settings->get('general.logo');
        $white = $settings->get('general.logo_white');

        $this->assertNotNull($dark);
        $this->assertNotNull($white);
        $this->assertNotSame($dark, $white, 'They are two separate files.');
        Storage::disk(config('store.uploads.disk'))->assertExists($dark);
        Storage::disk(config('store.uploads.disk'))->assertExists($white);
    }

    public function test_the_storefront_shows_the_uploaded_logos(): void
    {
        $this->save([
            'logo' => UploadedFile::fake()->image('dark.png', 320, 90),
            'logo_white' => UploadedFile::fake()->image('white.png', 320, 90),
        ]);

        $settings = app(SettingsService::class);
        $page = $this->get('/')->assertOk();

        $page->assertSee($settings->get('general.logo'), false);
        $page->assertSee($settings->get('general.logo_white'), false);
        $page->assertDontSee('furnivo-logo-black.png', false);
    }

    public function test_without_uploads_the_designs_own_logos_are_used(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('furnivo-logo-black.png', false)
            ->assertSee('furnivo-logo-white.png', false);
    }

    public function test_each_logo_can_be_removed_on_its_own(): void
    {
        $this->save([
            'logo' => UploadedFile::fake()->image('dark.png', 320, 90),
            'logo_white' => UploadedFile::fake()->image('white.png', 320, 90),
        ]);
        $white = app(SettingsService::class)->get('general.logo_white');

        $this->save(['remove_logo' => 1]);

        $this->assertNull(app(SettingsService::class)->get('general.logo'));
        $this->assertSame($white, app(SettingsService::class)->get('general.logo_white'), 'The white one is untouched.');
    }
}
