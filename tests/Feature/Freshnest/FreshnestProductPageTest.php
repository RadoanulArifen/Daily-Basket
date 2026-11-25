<?php

namespace Tests\Feature\Freshnest;

use App\Models\Product;
use App\Models\User;

/** The product page from the design, filled from the product, its brand, deals and reviews. */
class FreshnestProductPageTest extends FreshnestTestCase
{
    public function test_buy_box_shows_pack_sizes_price_grower_and_sku(): void
    {
        $this->seedFreshnest();

        $this->get('/products/organic-hass-avocado')->assertOk()
            ->assertSeeInOrder(['Home', 'Shop', 'Fruits', 'Organic Hass Avocado'])
            ->assertSee('Grown by')->assertSee('Sunridge Orchards')
            ->assertSee('Pack size')->assertSee('500 g · 3 pcs')->assertSee('2 × 500 g · 3 pcs', false)->assertSee('Family pack')
            ->assertSee('$4.20')->assertSee('$5.60')->assertSee('You save')
            ->assertSee('Add to Cart · $4.20', false)
            ->assertSee('GRC-1037')->assertSee('Sonoma County, CA')
            ->assertSee('45-min delivery')->assertSee('Free over $50');
    }

    public function test_coupon_box_lists_live_codes_and_applies_to_cart(): void
    {
        $this->seedFreshnest();

        $this->get('/products/organic-hass-avocado')->assertSee('Have a coupon?')->assertSee('FRESH5')->assertSee('FIRST10');
        $this->post('/cart/discount', ['code' => 'FIRST10'])->assertRedirect();
    }

    public function test_offers_on_this_item_come_from_its_deals(): void
    {
        $this->seedFreshnest();

        $this->get('/products/alphonso-mangoes')->assertOk()
            ->assertSee('Offers on this item')->assertSee('Buy 1 Get 1')->assertSee('GET 1 FREE')
            ->assertSee('Add 2 for $9.80', false)->assertSee('Code FRESH5');
        $this->get('/products/baby-leaf-spinach')->assertOk()->assertDontSee('Buy 1 Get 1');
    }

    public function test_tabs_show_description_storage_nutrition_and_reviews(): void
    {
        $this->seedFreshnest();

        $this->get('/products/free-range-eggs')->assertOk()
            ->assertSee('Description')->assertSee('Storage &amp; use', false)->assertSee('Keep refrigerated at 1–4°C.', false)
            ->assertSee('Nutrition')->assertSee('Per 100 g')->assertSee('Calcium')->assertSee('120 mg')
            ->assertSee('Reviews (3)')->assertSee('Marisol Vega')->assertSee('✓ Verified purchase', false)
            ->assertSee('You may also like');
    }

    public function test_no_nutrition_tab_without_data(): void
    {
        $this->seedFreshnest();
        Product::query()->where('slug', 'farmhouse-butter')->firstOrFail()->forceFill(['dimensions' => null])->saveQuietly();

        $this->get('/products/farmhouse-butter')->assertOk()->assertDontSee('Per 100 g');
    }

    public function test_signed_in_customer_can_write_a_review(): void
    {
        $this->seedFreshnest();
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->get('/products/rainbow-chard')->assertSee('Write a review')->assertSee('Post review');
        $this->actingAs($user, 'web')->post('/products/rainbow-chard/reviews', ['rating' => 5, 'body' => 'Crisp and colourful, lasted all week.'])->assertRedirect();
        $this->assertDatabaseHas('product_reviews', ['user_id' => $user->id, 'rating' => 5]);
    }
}
