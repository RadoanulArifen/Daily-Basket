<?php

namespace Tests\Feature\Furnivo;

use App\Models\Currency;
use App\Models\Menu;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Cache;

class FurnivoStorefrontWordingTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
        $this->seed(\Database\Seeders\FurnivoContentSeeder::class);
    }

    public function test_the_reassurances_under_the_bag_button_can_be_changed(): void
    {
        $this->get('/products/lucca-sofa')->assertOk()->assertSee('5-year warranty');

        app(SettingsService::class)->setMany('theme', ['trust_badges' => ['en' => "Free delivery\nLifetime guarantee"]]);

        $page = $this->get('/products/lucca-sofa')->assertOk();
        $page->assertSee('Free delivery')->assertSee('Lifetime guarantee')->assertDontSee('5-year warranty');
    }

    public function test_footer_headings_follow_the_menu_names(): void
    {
        $this->get('/')->assertOk()->assertSee('Support');

        Menu::query()->where('handle', 'footer-support')->firstOrFail()->update(['name' => 'Help']);

        $this->get('/')->assertOk()->assertSee('Help');
    }

    public function test_the_categories_heading_can_be_changed(): void
    {
        app(SettingsService::class)->setMany('theme', ['footer_categories_heading' => ['en' => 'Shop']]);

        $this->get('/')->assertOk()->assertSee('Shop');
    }

    public function test_the_offers_page_has_its_own_address(): void
    {
        $this->get('/offers')->assertRedirect('/collections/sale');
        $this->get('/collections/sale')->assertOk()->assertSee(__('store.collections.sale_text'));
    }

    public function test_the_currency_switcher_only_appears_when_there_is_a_choice(): void
    {
        $this->get('/')->assertOk()->assertDontSee('currency-switch', false);

        // A second currency, and the service's cache cleared as the admin does on save.
        Currency::query()->where('code', '!=', config('store.base_currency'))->update(['is_active' => true]);
        Cache::flush();
        app()->forgetInstance(\App\Services\Currency\CurrencyService::class);

        $this->get('/')->assertOk()->assertSee('currency-switch', false);
    }
}
