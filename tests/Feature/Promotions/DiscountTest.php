<?php

namespace Tests\Feature\Promotions;

use App\Models\Admin;
use App\Models\Discount;
use App\Models\Order;
use Tests\Feature\Checkout\CheckoutTestCase;

class DiscountTest extends CheckoutTestCase
{
    private function bagOf(string $price = '100.00', int $qty = 1): void
    {
        $this->addToBag($this->makeProduct('Throw '.uniqid(), [], [['price' => $price]])->variants()->first(), $qty);
    }

    public function test_percentage_code_applies_in_bag_and_order_with_vat_on_the_reduced_amount(): void
    {
        Discount::create(['code' => 'welcome10', 'type' => 'percentage', 'value' => '10']);
        $this->bagOf('150.00');

        $this->cookieRequest()->post('/cart/discount', ['code' => ' Welcome10 '])->assertSessionHasNoErrors();
        $this->cookieRequest()->get('/cart')->assertSee('Discount (WELCOME10)')->assertSee("−AED\u{00A0}15.00");
        $this->cookieRequest()->get('/checkout')->assertOk()->assertSee('Discount (WELCOME10)');

        $this->cookieRequest()->post('/checkout', $this->checkoutData())->assertSessionHasNoErrors();
        $order = Order::sole();

        // 150 − 15 = 135 → under 200, so AED 25 delivery → 160. VAT inside: 160 × 5/105 = 7.62
        $this->assertSame('15.00', $order->discount_total->toDecimal());
        $this->assertSame('WELCOME10', $order->discount_code);
        $this->assertSame('160.00', $order->grand_total->toDecimal());
        $this->assertSame('7.62', $order->tax_total->toDecimal());
        $this->assertSame(1, Discount::first()->used_count);
        $this->assertNull(\App\Models\Cart::first()->discount_code);
    }

    public function test_fixed_and_free_delivery_codes(): void
    {
        Discount::create(['code' => 'SHIPFREE', 'type' => 'free_shipping']);
        $this->bagOf('100.00');
        $this->cookieRequest()->post('/cart/discount', ['code' => 'shipfree']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $this->assertSame('0.00', Order::sole()->shipping_total->toDecimal());
        $this->assertSame('100.00', Order::sole()->grand_total->toDecimal());

        Order::query()->delete();
        $this->cartToken = null;
        Discount::create(['code' => 'BIG', 'type' => 'fixed', 'value' => '500.00']);
        $this->bagOf('80.00');
        $this->cookieRequest()->post('/cart/discount', ['code' => 'BIG']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        // A fixed amount never exceeds the items: 80 off 80, plus delivery 25.
        $this->assertSame('80.00', Order::sole()->discount_total->toDecimal());
        $this->assertSame('25.00', Order::sole()->grand_total->toDecimal());
    }

    public function test_rules_are_enforced(): void
    {
        $this->bagOf('100.00');
        Discount::create(['code' => 'OFF', 'type' => 'percentage', 'value' => '10', 'is_active' => false]);
        Discount::create(['code' => 'LATER', 'type' => 'percentage', 'value' => '10', 'starts_at' => now()->addDay()]);
        Discount::create(['code' => 'OLD', 'type' => 'percentage', 'value' => '10', 'ends_at' => now()->subDay()]);
        Discount::create(['code' => 'BIGBAG', 'type' => 'percentage', 'value' => '10', 'min_subtotal' => '300.00']);
        $gone = Discount::create(['code' => 'GONE', 'type' => 'percentage', 'value' => '10', 'usage_limit' => 1]);
        $gone->forceFill(['used_count' => 1])->save();

        foreach (['NOPE' => 'isn’t valid', 'OFF' => 'isn’t valid', 'LATER' => 'isn’t active yet', 'OLD' => 'expired', 'BIGBAG' => 'at least', 'GONE' => 'fully used'] as $code => $message) {
            $this->cookieRequest()->post('/cart/discount', ['code' => $code])->assertSessionHasErrors('code');
            $this->assertStringContainsString($message, session('errors')->first('code'), $code);
        }
    }

    public function test_per_customer_limit_is_checked_against_the_checkout_email(): void
    {
        Discount::create(['code' => 'ONCE', 'type' => 'percentage', 'value' => '10', 'usage_limit_per_customer' => 1]);

        $this->bagOf();
        $this->cookieRequest()->post('/cart/discount', ['code' => 'ONCE']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData())->assertSessionHasNoErrors();

        $this->bagOf();
        $this->cookieRequest()->post('/cart/discount', ['code' => 'ONCE'])->assertSessionHasNoErrors(); // email unknown yet
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['email' => 'MARIAM@example.com']))
            ->assertSessionHasErrors('discount');
        $this->assertSame(1, Order::count());

        // A different customer can still use it.
        $this->cookieRequest()->post('/cart/discount', ['code' => 'ONCE']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['email' => 'other@example.com']))->assertSessionHasNoErrors();
        $this->assertSame(2, Discount::first()->used_count);
    }

    public function test_a_code_that_stops_qualifying_is_removed_with_a_notice_and_cancelling_frees_the_use(): void
    {
        Discount::create(['code' => 'MIN150', 'type' => 'fixed', 'value' => '20', 'min_subtotal' => '150.00', 'usage_limit' => 1]);
        $variant = $this->makeProduct('Lamp', [], [['price' => '100.00']])->variants()->first();
        $this->addToBag($variant, 2);
        $this->cookieRequest()->post('/cart/discount', ['code' => 'MIN150'])->assertSessionHasNoErrors();

        $item = \App\Models\CartItem::first();
        $this->cookieRequest()->patch("/cart/items/{$item->id}", ['quantity' => 1]);
        $this->cookieRequest()->get('/cart')->assertSee('Code MIN150 was removed');

        $this->addToBag($variant, 1);
        $this->cookieRequest()->post('/cart/discount', ['code' => 'MIN150']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $this->assertSame(1, Discount::first()->used_count);

        $this->actingAs(Admin::factory()->create(), 'admin')->post('/admin/orders/'.Order::sole()->id.'/cancel');
        $this->assertSame(0, Discount::first()->fresh()->used_count);
    }

    public function test_admin_manages_discounts(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/discounts')->assertOk();
        $this->get('/admin/discounts/create')->assertOk();
        $this->post('/admin/discounts', ['code' => 'eid25', 'type' => 'percentage', 'value' => '25', 'is_active' => '1', 'ends_at' => '2030-01-01T00:00'])->assertSessionHasNoErrors();
        $discount = Discount::sole();
        $this->assertSame('EID25', $discount->code);
        $this->assertSame('2029-12-31 20:00:00', $discount->ends_at->utc()->format('Y-m-d H:i:s')); // UAE midnight = 20:00 UTC

        $this->post('/admin/discounts', ['code' => 'EID25', 'type' => 'percentage', 'value' => '25'])->assertSessionHasErrors('code');
        $this->post('/admin/discounts', ['code' => 'BAD CODE!', 'type' => 'percentage', 'value' => '150'])->assertSessionHasErrors(['code', 'value']);
        $this->post('/admin/discounts', ['code' => 'FREESHIP', 'type' => 'free_shipping'])->assertSessionHasNoErrors();

        $this->get('/admin/discounts')->assertSee('EID25')->assertSee('25% off')->assertSee('Free delivery');
        $this->delete("/admin/discounts/{$discount->id}")->assertRedirect('/admin/discounts');
    }
}
