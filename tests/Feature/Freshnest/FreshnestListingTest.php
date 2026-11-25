<?php

namespace Tests\Feature\Freshnest;

/** Shop, category and search pages in the design's listing. */
class FreshnestListingTest extends FreshnestTestCase
{
    public function test_shop_lists_every_product_in_the_design_grid(): void
    {
        $this->seedFreshnest();

        $this->get('/shop')->assertOk()
            ->assertSee('Every aisle in one place.', false)->assertDontSee('All products')->assertSee('Departments')->assertSee('Showing <strong style="color:#1F2A1F;">16</strong> of 16 products', false)
            ->assertSee('Fruits')->assertSee('Organic Hass Avocado')->assertSee('Cinnamon Morning Buns')
            ->assertSee('On offer')->assertSee('In stock today')->assertSee('Top rated')
            ->assertDontSee('Member price')->assertDontSee('Rated 4.5 and up');
    }

    public function test_category_page_has_band_breadcrumb_and_only_its_products(): void
    {
        $this->seedFreshnest();

        $this->get('/collections/fruits')->assertOk()
            ->assertSeeInOrder(['Home', 'Shop', 'Fruits'])
            ->assertSee('Tree-ripened and picked at dawn', false)
            ->assertSee('Alphonso Mangoes')->assertDontSee('Farmhouse Butter');
    }

    public function test_filters_and_sort_work(): void
    {
        $this->seedFreshnest();

        $this->get('/shop?sort=price_desc')->assertOk()->assertSeeInOrder(['Alphonso Mangoes', 'Baby Leaf Spinach']);
        $this->get('/shop?sort=rating')->assertOk()->assertSee('<option value="rating" selected', false);
        $this->get('/shop?max=3')->assertOk()->assertSee('Baby Leaf Spinach')->assertDontSee('Alphonso Mangoes');
    }

    public function test_search_uses_the_listing(): void
    {
        $this->seedFreshnest();

        $this->get('/search?q=bread')->assertOk()->assertSee('Results for “bread”', false)->assertSee('Seeded Rye Bread');
        $this->get('/search?q=zzzz')->assertOk()->assertSee('Nothing matched', false);
    }

    public function test_listing_pages_by_sixteen(): void
    {
        $this->seedFreshnest();
        $this->assertSame(16, \App\Services\Theme\ThemeSettings::PER_PAGE['freshnest']);
        config(['store.theme' => 'furnivo']);
        $this->assertSame(9, app(\App\Services\Catalog\StorefrontCatalog::class)->paginate(\App\Models\Product::query())->perPage());
    }
}
