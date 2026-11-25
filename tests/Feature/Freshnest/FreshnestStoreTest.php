<?php

namespace Tests\Feature\Freshnest;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use Database\Seeders\FreshnestSeeder;

/** The FreshNest seeder builds the store the design shows. */
class FreshnestStoreTest extends FreshnestTestCase
{
    public function test_the_catalogue_comes_from_the_design(): void
    {
        $this->seedFreshnest();

        $this->assertSame(['fruits', 'vegetables', 'dairy', 'bakery', 'meat-fish', 'beverages', 'snacks', 'pantry'],
            Category::query()->orderBy('sort_order')->pluck('slug')->all());
        $this->assertSame(16, Product::query()->count());
        $this->assertSame(4, Brand::query()->count());

        $avocado = Product::query()->where('slug', 'organic-hass-avocado')->with(['variants', 'brandRecord', 'categories'])->firstOrFail();
        $this->assertSame('Sunridge Orchards', $avocado->brandRecord->name);
        $this->assertSame('fruits', $avocado->categories->first()->slug);
        $this->assertSame(['4.20', '7.98', '14.28'], $avocado->variants->sortBy('position')->map(fn ($v) => $v->price->toDecimal())->values()->all());
        $this->assertSame('5.60', $avocado->variants->sortBy('position')->first()->compare_at_price->toDecimal());
        $this->assertSame('GRC-1037', $avocado->variants->sortBy('position')->first()->sku);
        $this->assertSame('4.67', number_format((float) $avocado->rating_average, 2, '.', ''));
        $this->assertSame(3, (int) $avocado->rating_count);
    }

    public function test_store_settings_follow_the_design(): void
    {
        $this->seedFreshnest();

        $this->assertSame('Daily Basket', settings()->trans('general.store_name'));
        $this->assertSame('4.99', (string) settings()->get('shipping.flat_rate'));
        $this->assertSame('50.00', (string) settings()->get('shipping.free_threshold'));
        $this->assertTrue(settings()->bool('payments.cod_enabled'));
        $this->assertSame(['en'], settings()->get('general.locales'));
    }

    public function test_menus_and_pages_are_created_without_grocers_branding(): void
    {
        $this->seedFreshnest();

        $this->assertSame('Only This Weekend|Super Discount', Menu::query()->where('handle', 'header-deals')->value('name'));
        foreach (['header', 'footer-shop', 'footer-company', 'footer-help'] as $handle) {
            $this->assertTrue(Menu::query()->where('handle', $handle)->firstOrFail()->items()->exists(), $handle);
        }
        $this->assertSame(12, Page::query()->withoutGlobalScopes()->count());
        $this->assertFalse((bool) Page::query()->withoutGlobalScopes()->where('slug', 'shop')->value('is_active'));
        $this->assertStringNotContainsStringIgnoringCase('grocers', (string) file_get_contents(FreshnestSeeder::DATA));
    }

    public function test_seeding_twice_adds_nothing(): void
    {
        $this->seedFreshnest();
        $this->seed(FreshnestSeeder::class);

        $this->assertSame(16, Product::query()->count());
    }

    public function test_homepage_content_can_be_added_to_an_older_freshnest_store(): void
    {
        $this->seedFreshnest();
        \App\Models\Banner::query()->delete();
        \App\Models\Promotion::query()->delete();

        $this->seed(\Database\Seeders\FreshnestHomepageSeeder::class);
        $this->assertTrue(\App\Models\Banner::query()->where('placement', 'weekend')->exists());
        $count = \App\Models\Banner::query()->count();

        $this->seed(\Database\Seeders\FreshnestHomepageSeeder::class);
        $this->assertSame($count, \App\Models\Banner::query()->count());
    }

    public function test_logo_is_set_for_the_admin_and_the_design_mark_stays_on_the_storefront(): void
    {
        $this->seedFreshnest();

        $this->assertSame(FreshnestSeeder::LOGO_PATH, settings()->get('general.logo'));
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists(FreshnestSeeder::LOGO_PATH);
        $this->get('/admin/login')->assertOk()->assertSee('daily-basket-logo.png', false);
        $this->get('/shop')->assertOk()->assertDontSee('daily-basket-logo.png', false)->assertSee('themes/freshnest/favicon.png?v=2', false);

        settings()->setMany('general', ['logo' => 'settings/my-own.png']);
        $this->get('/shop')->assertSee('my-own.png', false);
    }
}
