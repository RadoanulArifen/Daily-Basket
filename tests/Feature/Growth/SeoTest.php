<?php

namespace Tests\Feature\Growth;

use App\Models\Page;
use App\Models\ProductReview;
use App\Models\User;
use Tests\Feature\Storefront\StorefrontTestCase;

class SeoTest extends StorefrontTestCase
{
    public function test_robots_points_to_the_sitemap_and_hides_private_pages(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')->assertSee('Disallow: /ar/checkout')
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_sitemap_lists_public_pages_in_both_languages_only(): void
    {
        $living = $this->category('living');
        $this->category('hidden', null, false);
        $this->makeProduct('Visible Throw', ['categories' => [$living->id]]);
        $this->makeProduct('Draft Throw', ['status' => 'draft']);
        Page::create(['slug' => 'about', 'title' => ['en' => 'About']]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertStringContainsString('<loc>'.url('/products/visible-throw').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/ar/products/visible-throw').'</loc>', $xml);
        $this->assertStringContainsString('hreflang="ar" href="'.url('/ar/collections/living').'"', $xml);
        $this->assertStringContainsString('/pages/about', $xml);
        $this->assertStringNotContainsString('draft-throw', $xml);
        $this->assertStringNotContainsString('/collections/hidden', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap is valid XML');

        // A new product appears straight away (the cache is cleared on save).
        $this->makeProduct('Brand New Lamp');
        $this->assertStringContainsString('brand-new-lamp', $this->get('/sitemap.xml')->getContent());
    }

    public function test_product_page_has_valid_product_and_breadcrumb_data(): void
    {
        $living = $this->category('living');
        $product = $this->makeProduct('Woven Cushion', ['categories' => [$living->id]], [['price' => '145.00', 'sku' => 'SC-1', 'stock_quantity' => 0]]);
        ProductReview::create(['product_id' => $product->id, 'user_id' => User::factory()->create()->id, 'name' => 'A', 'rating' => 4, 'body' => 'Good cushion', 'status' => 'approved', 'locale' => 'en']);
        ProductReview::refreshProductRating($product->id);

        $html = $this->get('/products/woven-cushion')->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $blocks = array_map(fn ($j) => json_decode($j, true), $m[1]);
        $productLd = collect($blocks)->firstWhere('@type', 'Product');
        $crumbs = collect($blocks)->firstWhere('@type', 'BreadcrumbList');

        $this->assertSame('Woven Cushion', $productLd['name']);
        $this->assertSame('145.00', $productLd['offers'][0]['price']);
        $this->assertSame('AED', $productLd['offers'][0]['priceCurrency']);
        $this->assertSame('https://schema.org/OutOfStock', $productLd['offers'][0]['availability']);
        $this->assertSame('SC-1', $productLd['sku']);
        $this->assertSame(1, $productLd['aggregateRating']['reviewCount']);
        $this->assertSame(['Home', 'Living', 'Woven Cushion'], array_column($crumbs['itemListElement'], 'name'));
    }

    public function test_json_ld_cannot_break_out_of_its_script_tag(): void
    {
        $this->makeProduct('Evil </script><script>alert(1)</script>');

        $this->get('/products/evil-scriptscriptalert1script')->assertOk()
            ->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_home_has_store_data_with_search_box(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('/search?q={search_term_string}', $html);
    }
}
