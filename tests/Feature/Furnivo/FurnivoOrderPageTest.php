<?php

namespace Tests\Feature\Furnivo;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartService;

class FurnivoOrderPageTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function guest()
    {
        $token = Cart::query()->value('token');

        return $token ? $this->withCredentials()->withCookie(CartService::COOKIE, $token) : $this;
    }

    private function placeOrder(): Order
    {
        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant, 'quantity' => 1, 'options' => ['Colour' => 'Ivory']])->assertOk();
        $this->guest()->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_the_confirmation_follows_the_design(): void
    {
        $order = $this->placeOrder();

        $this->guest()->get("/orders/{$order->access_token}")->assertSeeInOrder([
            'Order confirmed', $order->number, 'buyer@example.com',
            'Payment', 'Cash on delivery', 'Delivery', 'Standard delivery',
            'Order progress', 'Confirmed', 'Shipped', 'Delivered',
            'Delivery address', 'Test Buyer', '12 Test Street', 'Jumeirah',
            'Nord Accent Chair', 'Colour: Ivory',
            'Subtotal', 'Total', 'Continue shopping', 'Invoice',
        ], false);
    }

    public function test_opening_the_order_later_is_not_a_confirmation(): void
    {
        $order = $this->placeOrder();

        $this->get("/orders/{$order->access_token}")->assertOk();   // the thank-you, once

        // Opening it again later shows the order itself, not the thank-you.
        $this->get("/orders/{$order->access_token}")->assertOk()
            ->assertDontSee('Order confirmed', false)
            ->assertSee($order->number)
            ->assertSee('Order progress', false);
    }

    public function test_the_progress_marks_the_stage_reached(): void
    {
        $order = $this->placeOrder();
        $order->forceFill(['fulfillment_status' => \App\Enums\FulfillmentStatus::Shipped])->save();

        $page = $this->get("/orders/{$order->access_token}")->assertOk()->getContent();

        // Two of the three steps are ticked once an order has shipped.
        $this->assertSame(2, substr_count($page, 'rgb(76, 175, 125); box-shadow'));
    }

    public function test_the_order_is_private_to_its_token(): void
    {
        $this->placeOrder();

        $this->get('/orders/not-a-real-token')->assertNotFound();
    }
}
