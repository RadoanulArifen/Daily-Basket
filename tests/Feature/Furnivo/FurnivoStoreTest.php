<?php

namespace Tests\Feature\Furnivo;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;

class FurnivoStoreTest extends FurnivoTestCase
{
    public function test_the_design_catalog_is_imported(): void
    {
        $this->seedFurnivo();

        $this->assertSame(104, Product::count());
        $this->assertSame(11, Brand::count());
        $this->assertSame(30, Category::count());
        $this->assertSame(3, Banner::where('placement', 'hero')->count());
        $this->assertTrue(Page::where('slug', 'faqs')->exists());
        $this->assertStringContainsString('<h2>What is covered</h2>', Page::where('slug', 'warranty-policy')->first()->getTranslation('body', 'en'));

        $table = Product::where('slug', 'monolith-table')->with(['variants', 'brandRecord', 'categories'])->first();
        $this->assertSame('24500.00', $table->variants->first()->price->toDecimal());
        $this->assertSame('USD', $table->variants->first()->price->currency);
        $this->assertSame('Al Huzaifa Furniture', $table->brandRecord->name);
        $this->assertSame('Dining Tables', $table->categories->first()->name);
        $this->assertTrue($table->made_to_order);
        $this->assertTrue($table->from_price);
        // The score comes from the reviews the store can actually show, not the design's
        // decorative number, so the page never claims more reviews than it has.
        $this->assertSame($table->reviews()->where('status', 'approved')->count(), $table->rating_count);
        $this->assertGreaterThan(0, (float) $table->rating_average);
        $this->assertFalse($table->variants->first()->track_inventory);   // made to order

        $this->assertSame('$', \App\Models\Currency::where('code', 'USD')->value('symbol'));
        $this->assertSame(['featured', 'best', 'new', 'top', 'trending'], array_column(settings()->get('furnivo.rails'), 'key'));
    }

    public function test_home_page_matches_the_design_content(): void
    {
        $this->seedFurnivo();

        $home = $this->get('/')->assertOk();
        $home->assertSee('We don&#039;t do average.', false)->assertSee('Explore the collection')
            ->assertSee('Shop by category')->assertSee('27</span> pieces', false)
            ->assertSee('Featured Products')->assertSee('Best Sellers')->assertSee('New Arrivals')->assertSee('Top Rated Pieces')->assertSee('Trending in Luxury')
            ->assertSee('Monolith Dining Table')->assertSee('24,500')->assertDontSee('USD24')
            ->assertSee('themes/furnivo/brand-logo-small-1.png', false)
            ->assertSee('Join the List')->assertSee('Custom Order');

        // Rows keep the design's order.
        $html = $home->getContent();
        $this->assertLessThan(strpos($html, 'Lucca 3-Seater Sofa'), strpos($html, 'Monolith Dining Table'));
    }

    public function test_design_photos_are_downloaded_once(): void
    {
        $this->seedFurnivo();
        $image = imagecreatetruecolor(64, 64);
        ob_start(); imagejpeg($image, null, 80); $jpeg = ob_get_clean();
        Http::fake(['images.unsplash.com/*' => Http::response($jpeg . str_repeat("\0", 1200), 200, ['Content-Type' => 'image/jpeg'])]);

        $this->artisan('store:import-design-images')->assertSuccessful();

        $this->assertSame(104, Product::query()->whereHas('media')->count());
        $this->assertSame(5, Category::whereNull('parent_id')->whereNotNull('image_id')->count());
        $this->assertSame(3, Banner::whereNotNull('image_id')->count());
        $this->assertCount(4, array_filter(array_column(settings()->get('furnivo.explore'), 'image_id')));

        $sent = count(Http::recorded());
        $this->artisan('store:import-design-images')->assertSuccessful();
        $this->assertSame($sent, count(Http::recorded()));   // nothing downloaded twice
    }

    public function test_newsletter_and_brand_pages(): void
    {
        $this->seedFurnivo();

        $this->postJson('/newsletter', ['email' => 'Guest@Example.com'])->assertOk();
        $this->postJson('/newsletter', ['email' => 'guest@example.com'])->assertOk();          // again: fine
        $this->postJson('/newsletter', ['email' => 'not-an-email'])->assertUnprocessable();
        $this->postJson('/newsletter', ['email' => 'bot@example.com', 'website' => 'x'])->assertOk();   // trap
        $this->assertSame(['guest@example.com'], Subscriber::pluck('email')->all());

        $this->get('/brands/alhuzaifa')->assertOk()->assertSee('Al Huzaifa Furniture')->assertSee('Monolith Dining Table');
        $this->get('/brands/nope')->assertNotFound();
    }
}
