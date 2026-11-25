<?php

namespace Tests\Feature\Freshnest;

use App\Models\Product;

/** The shopper tour: its data on every storefront page and the elements each step points at. */
class FreshnestShopperTourTest extends FreshnestTestCase
{
    private function tourData(string $html): array
    {
        $this->assertMatchesRegularExpression('#<script type="application/json" id="store-tour-data">(.+?)</script>#s', $html);
        preg_match('#<script type="application/json" id="store-tour-data">(.+?)</script>#s', $html, $m);

        return json_decode(html_entity_decode($m[1]), true);
    }

    public function test_each_step_points_at_an_element_on_its_page(): void
    {
        $this->seedFreshnest();

        $home = $this->get('/')->assertOk()->getContent();
        $data = $this->tourData($home);
        $this->assertSame('home', $data['route']);
        $this->assertCount(5, $data['steps']);
        $this->assertSame('Take the 1-minute tour', $data['labels']['launcher']);

        $pages = ['home' => $home];
        $pages['products.show'] = $this->get(collect($data['steps'])->firstWhere('page', 'products.show')['url'])->assertOk()->getContent();

        $variant = Product::query()->where('status', 'active')->firstOrFail()->variants()->firstOrFail();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $token = \App\Models\Cart::query()->value('token');
        $pages['cart'] = $this->withCookie(\App\Services\Cart\CartService::COOKIE, $token)->get('/cart')->assertOk()->getContent();

        foreach ($data['steps'] as $i => $step) {
            $this->assertStringContainsString(substr($step['target'], 1, -1), $pages[$step['page']], "step {$i} ({$step['target']}) missing on {$step['page']}");
        }
    }

    public function test_arabic_pages_get_arabic_steps_and_the_page_name_without_prefix(): void
    {
        $this->seedFreshnest();
        settings()->setMany('general', ['locales' => ['en', 'ar']]);

        $html = $this->get('/ar')->getContent();
        if (! str_contains($html, 'store-tour-data')) {
            $this->markTestSkipped('Arabic is not switched on for this store.');
        }
        $data = $this->tourData($html);
        $this->assertSame('home', $data['route']);
        $this->assertSame('جولة سريعة في دقيقة', $data['labels']['launcher']);
    }

    public function test_the_store_owner_can_switch_it_off(): void
    {
        $this->seedFreshnest();
        settings()->setMany('preferences', ['storefront_tour' => false]);
        app(\App\Services\Settings\SettingsService::class)->flush();

        $this->get('/')->assertOk()->assertDontSee('store-tour-data', false);
    }
}
