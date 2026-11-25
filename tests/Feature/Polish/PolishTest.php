<?php

namespace Tests\Feature\Polish;

use App\Models\Admin;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Checkout\CheckoutTestCase;

class PolishTest extends CheckoutTestCase
{
    public function test_every_html_page_has_a_csp_with_a_nonce(): void
    {
        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+' 'unsafe-eval'/", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self' https://checkout.stripe.com", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('googletagmanager', $csp);

        $this->assertStringContainsString("frame-ancestors 'none'", $this->get('/admin/login')->headers->get('Content-Security-Policy'));
        $this->get('/sitemap.xml')->assertHeaderMissing('Content-Security-Policy'); // XML, not HTML
    }

    public function test_no_page_uses_inline_event_handlers_that_the_csp_would_block(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $this->addToBag(Product::where('slug', 'brass-dallah')->first()->variants()->first());

        $pages = ['/', '/shop', '/collections/living', '/products/woven-wool-cushion', '/search?q=candle', '/pages/contact', '/ar'];
        foreach ($pages as $url) {
            $this->assertDoesNotMatchRegularExpression('/\son(click|change|submit|keydown|input|load)=/i', $this->get($url)->getContent(), $url);
        }
        $this->assertDoesNotMatchRegularExpression('/\son(click|change|submit|keydown)=/i', $this->cookieRequest()->get('/cart')->getContent(), '/cart');

        $this->actingAs(Admin::firstOrFail(), 'admin');
        foreach (['/admin/products', '/admin/orders', '/admin/customers', '/admin/activity'] as $url) {
            $this->assertDoesNotMatchRegularExpression('/\son(click|change|submit|keydown)=/i', $this->get($url)->getContent(), $url);
        }
    }

    public function test_google_analytics_loads_only_on_the_storefront_when_configured(): void
    {
        $this->get('/')->assertDontSee('googletagmanager.com/gtag', false);

        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->put('/admin/settings/tracking', ['ga_measurement_id' => 'ua-123'])->assertSessionHasErrors('ga_measurement_id');
        $this->put('/admin/settings/tracking', ['ga_measurement_id' => ' g-abc123xyz9 '])->assertSessionHasNoErrors();
        $this->assertSame('G-ABC123XYZ9', settings()->get('tracking.ga_measurement_id'));

        $home = $this->get('/');
        $home->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ9', false);
        $this->assertStringContainsString('https://www.googletagmanager.com', $home->headers->get('Content-Security-Policy'));
        $this->assertMatchesRegularExpression('/<script nonce="[A-Za-z0-9]+">\s*window.dataLayer/', $home->getContent());

        $this->get('/admin/settings/tracking')->assertDontSee('googletagmanager.com/gtag', false);
    }

    public function test_a_completed_order_is_reported_to_analytics_once(): void
    {
        settings()->setMany('tracking', ['ga_measurement_id' => 'G-TEST1234']);
        $this->addToBag($this->makeProduct('Dallah', [], [['price' => '240.00', 'sku' => 'DL-1']])->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = \App\Models\Order::sole();

        $this->withSession(['placed' => true])->get('/orders/'.$order->access_token)
            ->assertSee("gtag('event', 'purchase'", false)->assertSee($order->number)->assertSee('DL-1', false);
        $this->get('/orders/'.$order->access_token)->assertDontSee("'purchase'", false); // not again on a revisit
    }

    public function test_logo_favicons_and_new_home_sections(): void
    {
        $home = $this->get('/')->assertOk();
        $home->assertSee('favicon.svg', false)->assertSee('site.webmanifest', false)->assertSee('apple-touch-icon.png', false);
        $home->assertSee('Handmade in the Emirates')->assertSee('A Bedouin craft')->assertSee('images/demo/scene-hero.webp', false);
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('images/demo/scene-story.webp'));
        $this->get('/ar')->assertSee('حكايتنا')->assertSee('صُنع يدوياً في الإمارات');
        $this->get('/admin/login')->assertSee('favicon.svg', false);
    }

    public function test_demo_catalog_uses_the_illustrated_photos_and_colours_match_variants(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $cushion = Product::where('slug', 'woven-wool-cushion')->with(['media', 'variants.optionValues', 'variants.image'])->first();
        $this->assertCount(4, $cushion->media);
        $this->assertSame('woven-wool-cushion-1.webp', $cushion->media[0]->original_name);

        $indigo = $cushion->variants->first(fn ($v) => str_contains($v->title('en'), 'Indigo'));
        $this->assertSame('woven-wool-cushion-2.webp', $indigo->image->original_name);

        // Refreshing demo images on an existing install swaps them without touching anything else.
        $price = $indigo->price->toDecimal();
        $this->artisan('store:demo-images')->assertSuccessful();
        $cushion->refresh()->load('media');
        $this->assertCount(4, $cushion->media);
        $this->assertSame($price, $indigo->fresh()->price->toDecimal());
        $this->assertSame(4, \App\Models\Media::query()->whereHas('products', fn ($q) => $q->whereKey($cushion->id))->count());
        // Categories got their own room photos, and replaced images were cleaned up (26 product images + none left over).
        $this->assertSame('woven-wool-cushion-3.webp', \App\Models\Category::where('slug', 'living')->first()->image->original_name);
        $this->assertSame(26, \App\Models\Media::count());
    }
}
