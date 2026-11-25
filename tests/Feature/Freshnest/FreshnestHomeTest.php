<?php

namespace Tests\Feature\Freshnest;

use App\Models\Banner;
use App\Models\Product;
use App\Services\Theme\ThemeSettings;

/** The FreshNest home page: every section from the design, filled from the admin's data. */
class FreshnestHomeTest extends FreshnestTestCase
{
    public function test_all_design_sections_render_in_order_with_store_data(): void
    {
        $this->seedFreshnest();

        $this->get('/')->assertOk()->assertSeeInOrder([
            'Harvested this morning', 'Premium Organic Produce,', 'Delivered Daily', '80 mi', 'Local growers',
            'Shop by Category', 'Meat &amp; Fish',
            'Weekend Savings Bonanza', 'OFF', 'GET 1', 'Fresh Dairy',
            "Today's Fresh Picks", 'Cold-pressed juices', 'Organic Hass Avocado',
            'Offers &amp; Bundles', 'GET 1 FREE', 'Code FRESH5', 'Spend $40, get', '$5 off', 'Buy 3 Save 15%',
            'Deal of the', 'Almond Croissants', 'Daily essentials',
            'Crisp greens, 25% off',
            'Top Rated', 'Best Sellers', 'New Arrivals', 'Up to 25% off fresh berries',
            'Daily Bakery &amp; Dairy',
            'Get 10% Off Your First Order', '10% OFF', 'FIRST10',
            '100% Organic Certified', 'Easy Returns',
        ], false);
    }

    public function test_banners_drive_the_words(): void
    {
        $this->seedFreshnest();
        Banner::query()->where('placement', 'weekend')->firstOrFail()->update(['title' => ['en' => 'Summer Fruit Fest'], 'text' => ['en' => '40% OFF|On stone fruit all week.']]);
        Banner::query()->where('placement', 'trust')->orderBy('sort_order')->firstOrFail()->update(['is_active' => false]);

        $this->get('/')->assertOk()
            ->assertSee('Summer Fruit Fest')->assertSee('On stone fruit all week.')->assertDontSee('Weekend Savings Bonanza')
            ->assertDontSee('100% Organic Certified');
    }

    public function test_sections_can_be_hidden_in_customize(): void
    {
        $this->seedFreshnest();
        settings()->setMany('theme', ['sections' => collect(ThemeSettings::THEME_SECTIONS['freshnest'])->map(fn ($k) => ['key' => $k, 'visible' => $k !== 'deal'])->all()]);

        $this->get('/')->assertOk()->assertDontSee('Daily essentials')->assertSee('Top Rated');
    }

    public function test_home_works_on_an_empty_store(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class]);

        $this->get('/')->assertOk()->assertDontSee('Deal of the');
    }

    public function test_deal_of_the_day_follows_the_banner_link(): void
    {
        $this->seedFreshnest();
        Banner::query()->where('placement', 'deal')->firstOrFail()->update(['url' => '/products/farmhouse-butter']);

        $html = $this->get('/')->assertOk()->getContent();
        $deal = substr($html, strpos($html, 'Deal of the'), 6000);
        $this->assertStringContainsString('Farmhouse Butter', $deal);
    }

    public function test_product_cards_add_to_cart_and_wishlist(): void
    {
        $this->seedFreshnest();
        $variant = Product::query()->where('slug', 'organic-hass-avocado')->firstOrFail()->variants()->orderBy('position')->firstOrFail();

        $this->get('/')->assertSee('name="variant_id" value="'.$variant->id.'"', false)->assertSee('/wishlist/', false);
    }
}
