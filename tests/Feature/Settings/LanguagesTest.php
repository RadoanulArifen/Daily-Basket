<?php

namespace Tests\Feature\Settings;

use App\Models\Admin;
use App\Models\Product;
use App\Services\Settings\SettingsService;
use App\Support\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
    }

    private function useLanguages(array $codes): void
    {
        app(SettingsService::class)->setMany('general', ['locales' => $codes]);
    }

    public function test_the_store_uses_only_the_languages_that_are_switched_on(): void
    {
        $this->useLanguages(['en']);
        $this->assertSame(['en'], array_keys(app(Localization::class)->locales()));

        $this->useLanguages(['en', 'ar']);
        $this->assertSame(['en', 'ar'], array_keys(app(Localization::class)->locales()));
    }

    public function test_the_main_language_can_never_be_switched_off(): void
    {
        $this->useLanguages(['ar']);

        $this->assertContains('en', array_keys(app(Localization::class)->locales()));
    }

    public function test_a_switched_off_language_is_not_served(): void
    {
        $this->useLanguages(['en']);
        $this->get('/ar')->assertRedirect('/');

        $this->useLanguages(['en', 'ar']);
        $this->get('/ar')->assertOk();
    }

    public function test_the_admin_only_asks_for_languages_in_use(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $product = Product::query()->firstOrFail();

        $this->useLanguages(['en']);
        $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertDontSee('العربية', false);

        $this->useLanguages(['en', 'ar']);
        $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('العربية', false);
    }

    public function test_switching_a_language_off_keeps_what_was_written_in_it(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->useLanguages(['en', 'ar']);

        $this->put('/admin/settings/general', [
            'store_name' => ['en' => 'Furnivo', 'ar' => 'فورنيفو'],
            'store_email' => 'hello@furnivo.com',
            'locales' => ['en'],
        ])->assertRedirect();

        $this->assertSame('فورنيفو', app(SettingsService::class)->get('general.store_name')['ar'] ?? null);
        $this->assertSame(['en'], app(SettingsService::class)->get('general.locales'));
    }
}
