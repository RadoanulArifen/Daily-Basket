<?php

namespace Tests\Feature\Storefront;

use App\Models\Currency;
use App\Services\Currency\CurrencyService;

class CatalogPagesTest extends StorefrontTestCase
{
    public function test_only_active_products_with_active_variants_are_shown(): void
    {
        $this->makeProduct('Visible Cushion');
        $this->makeProduct('Draft Cushion', ['status' => 'draft']);
        $this->makeProduct('Archived Cushion', ['status' => 'archived']);
        $this->makeProduct('Hidden Variant Cushion', [], [['is_active' => false]]);
        $this->makeProduct('Deleted Cushion')->delete();

        $this->get('/shop')->assertOk()
            ->assertSee('Visible Cushion')
            ->assertDontSee('Draft Cushion')
            ->assertDontSee('Archived Cushion')
            ->assertDontSee('Hidden Variant Cushion')
            ->assertDontSee('Deleted Cushion');

        $this->get('/products/draft-cushion')->assertNotFound();
        $this->get('/products/deleted-cushion')->assertNotFound();
    }

    public function test_category_page_includes_subcategories_and_hides_inactive_categories(): void
    {
        $living = $this->category('living');
        $cushions = $this->category('cushions', $living);
        $hidden = $this->category('hidden');

        $this->makeProduct('Parent Item', ['categories' => [$living->id]]);
        $this->makeProduct('Child Item', ['categories' => [$cushions->id]]);
        $this->makeProduct('Elsewhere');
        $hidden->update(['is_active' => false]);

        $this->get('/collections/living')->assertOk()
            ->assertSee('Parent Item')->assertSee('Child Item')->assertDontSee('Elsewhere')
            ->assertSee('/collections/cushions', false);

        $this->get('/collections/cushions')->assertOk()->assertSee('Child Item')->assertDontSee('Parent Item');
        $this->get('/collections/hidden')->assertNotFound();
        $this->get('/collections/nope')->assertNotFound();
    }

    public function test_sale_and_new_in_collections(): void
    {
        $this->makeProduct('Discounted Throw', [], [['price' => '80.00', 'compare_at_price' => '100.00']]);
        $this->makeProduct('Full Price Throw');

        $this->get('/collections/sale')->assertOk()->assertSee('Discounted Throw')->assertDontSee('Full Price Throw');
        $this->get('/collections/new-in')->assertOk()->assertSee('Discounted Throw')->assertSee('Full Price Throw');
    }

    public function test_filters_and_sorting(): void
    {
        $this->makeProduct('Cheap Candle', [], [['price' => '50.00']]);
        $this->makeProduct('Mid Candle', [], [['price' => '150.00', 'stock_quantity' => 0]]);
        $this->makeProduct('Dear Candle', [], [['price' => '300.00']]);

        $this->get('/shop?sort=price_asc')->assertSeeInOrder(['Cheap Candle', 'Mid Candle', 'Dear Candle']);
        $this->get('/shop?sort=price_desc')->assertSeeInOrder(['Dear Candle', 'Mid Candle', 'Cheap Candle']);
        $this->get('/shop?min=100&max=200')->assertSee('Mid Candle')->assertDontSee('Cheap Candle')->assertDontSee('Dear Candle');
        $this->get('/shop?in_stock=1')->assertDontSee('Mid Candle')->assertSee('Cheap Candle');

        // Rubbish in the URL is ignored, not an error.
        $this->get('/shop?sort=drop-table&min=abc')->assertOk();
    }

    public function test_price_filter_uses_the_shoppers_currency(): void
    {
        // 1 AED = 0.25 in a test currency, so AED 100 shows as 25.00.
        Currency::where('code', 'USD')->update(['exchange_rate' => '0.250000', 'is_active' => true]);
        app(CurrencyService::class)->flush();

        $this->makeProduct('Hundred Dirham Bowl', [], [['price' => '100.00']]);

        $this->withSession(['currency' => 'USD'])->get('/shop?min=20&max=30')->assertSee('Hundred Dirham Bowl');
        $this->withSession(['currency' => 'USD'])->get('/shop?min=90')->assertDontSee('Hundred Dirham Bowl');
    }

    public function test_search_finds_english_arabic_and_sku(): void
    {
        $this->makeProduct('Brass Dallah', ['ar' => 'دلة نحاسية'], [['sku' => 'SH-DAL-1']]);
        $this->makeProduct('Wool Throw');

        $this->get('/search?q=dallah')->assertOk()->assertSee('Brass Dallah')->assertDontSee('Wool Throw');
        $this->get('/search?q='.urlencode('نحاسية'))->assertSee('Brass Dallah');
        $this->get('/search?q=sh-dal')->assertSee('Brass Dallah');
        $this->get('/search?q=zzzz')->assertOk()->assertSee('No results');
        $this->get('/search?q=a')->assertOk()->assertSee('Type at least 2');
        $this->get('/search')->assertOk();
    }

    public function test_product_page_shows_options_prices_and_stock(): void
    {
        $product = $this->sizedProduct();

        $response = $this->get('/products/woven-cushion')->assertOk()
            ->assertSee('Woven Cushion')->assertSee('Small')->assertSee('Large')
            ->assertSee('x-data="productPage(', false)
            ->assertSee('Price includes VAT');

        // The in-stock variant is chosen by default.
        $small = $product->variants()->orderBy('position')->first();
        $response->assertSee('name="variant_id" value="'.$small->id.'"', false);

        // ?variant= opens a specific one.
        $large = $product->variants()->orderBy('position')->skip(1)->first();
        $this->get('/products/woven-cushion?variant='.$large->id)->assertSee('name="variant_id" value="'.$large->id.'"', false);
    }

    public function test_home_and_arabic_pages_render(): void
    {
        $living = $this->category('living');
        $this->makeProduct('Featured Cushion', ['featured' => true, 'ar' => 'وسادة مميزة', 'categories' => [$living->id]]);
        $this->makeProduct('On Sale Candle', [], [['price' => '50.00', 'compare_at_price' => '70.00']]);

        $this->get('/')->assertOk()->assertSee('Featured Cushion')->assertSee('/collections/living', false)->assertSee('/collections/sale', false);

        $this->get('/ar')->assertOk()->assertSee('وسادة مميزة')->assertSee('dir="rtl"', false);
        $this->get('/ar/products/featured-cushion')->assertOk()->assertSee('وسادة مميزة')->assertSee('/ar/cart/items', false);
        $this->get('/ar/collections/living')->assertOk()->assertSee('وسادة مميزة');
        $this->get('/ar/shop')->assertOk();
        $this->get('/ar/search?q='.urlencode('وسادة'))->assertOk()->assertSee('وسادة مميزة');
    }

    public function test_filtered_listings_are_not_indexed(): void
    {
        $this->get('/shop')->assertDontSee('noindex');
        $this->get('/shop?sort=newest')->assertSee('noindex');
    }
}
