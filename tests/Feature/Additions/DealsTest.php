<?php

namespace Tests\Feature\Additions;

use App\Models\Admin;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Promotion;
use Tests\Feature\Checkout\CheckoutTestCase;

class DealsTest extends CheckoutTestCase
{
    private function candle(string $name, string $price, array $attrs = []) { return $this->makeProduct($name, $attrs, [['price' => $price, 'stock_quantity' => 20]])->variants()->first(); }

    public function test_buy_two_get_one_free_makes_the_cheapest_free(): void
    {
        Promotion::create(['name' => ['en' => 'Candle trio'], 'buy_quantity' => 2, 'get_quantity' => 1]);
        $this->addToBag($this->candle('Big', '160.00'));
        $this->addToBag($this->candle('Mid', '95.00'));
        $this->addToBag($this->candle('Small', '85.00'));

        $this->cookieRequest()->get('/cart')->assertSee('Deal: Candle trio')->assertSee("−AED\u{00A0}85.00");

        $r = $this->cookieRequest()->post('/checkout', $this->checkoutData());
        fwrite(STDERR, "\nSTATUS ".$r->status().' -> '.$r->headers->get('Location').' errors: '.json_encode(session('errors')?->all())."\n");
        $order = Order::sole();
        $this->assertSame('85.00', $order->promotion_total->toDecimal());
        $this->assertSame('340.00', $order->subtotal->toDecimal());
        $this->assertSame('255.00', $order->grand_total->toDecimal());   // 340 − 85, free delivery over 200
        $this->assertSame('12.14', $order->tax_total->toDecimal());      // VAT on what's paid: 255 × 5/105
        $this->assertSame('Candle trio', $order->dealLines()[0]['label']);
    }

    public function test_only_full_groups_count_and_half_price_works(): void
    {
        Promotion::create(['buy_quantity' => 1, 'get_quantity' => 1, 'get_percent' => 50]);
        $this->addToBag($this->candle('Pair', '100.00'), 3); // one full pair + one left over

        $this->cookieRequest()->get('/cart')->assertSee('Deal: Buy 1, get 1 at 50% off')->assertSee("−AED\u{00A0}50.00");
    }

    public function test_category_and_product_scopes_and_no_stacking(): void
    {
        $living = $this->category('living');
        $candles = $this->category('candles', $living);
        $inCandles = $this->candle('Oud', '100.00', ['categories' => [$candles->id]]);
        $other = $this->candle('Bowl', '100.00');

        Promotion::create(['buy_quantity' => 1, 'get_quantity' => 1, 'applies_to' => 'category', 'category_id' => $living->id]);  // covers sub-category
        Promotion::create(['buy_quantity' => 1, 'get_quantity' => 1, 'applies_to' => 'products', 'product_ids' => [$inCandles->product_id, $other->product_id]]);

        $this->addToBag($inCandles, 2);
        $this->addToBag($other, 1);

        // First deal takes both Oud candles (100 off); the Bowl alone can't make a pair for the second deal.
        $summary = $this->cookieRequest()->get('/cart');
        $summary->assertSee("−AED\u{00A0}100.00");
        $this->assertSame(1, substr_count($summary->getContent(), 'Deal:'));
    }

    public function test_discount_codes_apply_after_deals_and_scheduled_deals_wait(): void
    {
        Promotion::create(['buy_quantity' => 1, 'get_quantity' => 1]);
        Promotion::create(['buy_quantity' => 1, 'get_quantity' => 1, 'starts_at' => now()->addDay(), 'name' => ['en' => 'Eid deal']]);
        Discount::create(['code' => 'TEN', 'type' => 'percentage', 'value' => '10']);
        $this->addToBag($this->candle('Pair', '100.00'), 2);
        $this->cookieRequest()->post('/cart/discount', ['code' => 'TEN']);

        $this->cookieRequest()->get('/cart')->assertDontSee('Eid deal');
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::sole();
        $this->assertSame('100.00', $order->promotion_total->toDecimal());
        $this->assertSame('10.00', $order->discount_total->toDecimal());   // 10% of the 100 left after the deal
        $this->assertSame('115.00', $order->grand_total->toDecimal());      // 90 + 25 delivery (under 200 after discounts)
    }

    public function test_product_page_badge_and_admin_screens(): void
    {
        $variant = $this->candle('Oud', '100.00');
        Promotion::create(['buy_quantity' => 2, 'get_quantity' => 1]);
        $this->get('/products/oud')->assertSee('Buy 2, get 1 free');
        $this->get('/ar/products/oud')->assertSee('اشترِ 2 واحصل على 1 مجاناً');

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/promotions')->assertOk()->assertSee('Buy 2, get 1 free');
        $this->get('/admin/promotions/create')->assertOk();
        $this->post('/admin/promotions', ['buy_quantity' => 3, 'get_quantity' => 1, 'get_percent' => 100, 'applies_to' => 'category'])->assertSessionHasErrors('category_id');
        $this->post('/admin/promotions', ['buy_quantity' => 3, 'get_quantity' => 1, 'get_percent' => 100, 'applies_to' => 'products', 'product_ids' => [$variant->product_id], 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([$variant->product_id], Promotion::latest('id')->first()->product_ids);
    }
}
