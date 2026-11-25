<?php

namespace Tests\Feature\Freshnest;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartService;

/** Checkout in the FreshNest look, placing a real cash-on-delivery order. */
class FreshnestCheckoutTest extends FreshnestTestCase
{
    private function withCartOf(string $slug, int $qty)
    {
        $variant = Product::query()->where('slug', $slug)->firstOrFail()->variants()->orderBy('position')->firstOrFail();
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => $qty])->assertOk();

        return $this->withCredentials()->withCookie(CartService::COOKIE, Cart::query()->value('token'));
    }

    public function test_checkout_shows_the_three_steps_and_the_summary(): void
    {
        $this->seedFreshnest();

        $this->withCartOf('baby-leaf-spinach', 2)->get('/checkout')->assertOk()
            ->assertSeeInOrder(['Contact', 'Delivery', 'Payment'])
            ->assertSee('Continue to delivery')->assertSee('Continue to payment')
            ->assertSee('Express delivery')->assertSee('$4.99')
            ->assertSee('Order summary')->assertSee('Baby Leaf Spinach')->assertSee('Coupon code');
    }

    public function test_a_cash_on_delivery_order_is_placed(): void
    {
        $this->seedFreshnest();
        $client = $this->withCartOf('baby-leaf-spinach', 2);

        $client->post('/checkout', [
            'email' => 'sam@example.com', 'name' => 'Sam Lee', 'phone' => '+1 415 555 0100',
            'address_line' => '18 Meadow Row', 'city' => 'San Francisco', 'emirate' => 'dubai',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('sam@example.com', $order->email);
        $this->assertSame('9.19', $order->grand_total->toDecimal());

        $this->withSession(['placed' => true])->get('/orders/'.$order->access_token)->assertOk()
            ->assertSee('Thank you, Sam!')->assertSee($order->number)
            ->assertSeeInOrder(['Order placed', 'Packed', 'Out for delivery', 'Delivered'])
            ->assertSee('Confirmed')->assertSee('Baby Leaf Spinach')->assertSee('$9.19')
            ->assertSee('Keep shopping')->assertSee('18 Meadow Row');

        $order->forceFill(['fulfillment_status' => \App\Enums\FulfillmentStatus::Delivered, 'delivered_at' => now()])->save();
        $this->get('/orders/'.$order->access_token)->assertOk()->assertSee('Order '.$order->number)->assertSee('Delivered')->assertDontSee('Thank you');
    }

    public function test_empty_cart_goes_back_to_the_cart(): void
    {
        $this->seedFreshnest();

        $this->get('/checkout')->assertRedirect();
    }
}
