<?php

namespace Tests\Feature\Furnivo;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartService;

class FurnivoCheckoutTest extends FurnivoTestCase
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

    private function fill(array $overrides = []): array
    {
        return array_merge([
            'email' => 'buyer@example.com',
            'name' => 'Test Buyer',
            'phone' => '+971501234567',
            'emirate' => 'dubai',
            'city' => 'Jumeirah',
            'address_line' => '12 Test Street',
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ], $overrides);
    }

    private function addToBag(string $slug = 'nord-accent'): void
    {
        $variant = Product::query()->where('slug', $slug)->firstOrFail()->variants()->value('id');
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant, 'quantity' => 1])->assertOk();
    }

    public function test_checkout_shows_the_designs_three_steps(): void
    {
        $this->addToBag();

        $this->guest()->get('/checkout')->assertOk()->assertSeeInOrder([
            'Home', 'Cart', 'Checkout',
            '1', 'Information', '2', 'Shipping', '3', 'Payment',
            'Contact', 'Continue to shipping',
            'Delivery address', 'Continue to payment',
            'Select payment option',
            'In your order', 'Nord Accent Chair', 'Qty', 'Subtotal', 'Delivery', 'Total',
        ], false);
    }

    public function test_an_empty_bag_cannot_reach_checkout(): void
    {
        $this->get('/checkout')->assertRedirect(store_route('cart'));
    }

    public function test_the_order_is_placed_from_the_last_step(): void
    {
        $this->addToBag();

        $this->guest()->post('/checkout', $this->fill())->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('buyer@example.com', $order->email);
        $this->assertSame('cod', $order->payment_method);
        $this->assertCount(1, $order->items);
    }

    public function test_a_missing_contact_field_returns_to_the_first_step(): void
    {
        $this->addToBag();

        $response = $this->guest()->from('/checkout')->post('/checkout', $this->fill(['email' => '']));
        $response->assertRedirect('/checkout')->assertSessionHasErrors('email');

        // The page reopens on step 1 so the shopper sees the problem.
        $this->guest()->followingRedirects()->post('/checkout', $this->fill(['email' => '']))
            ->assertOk()->assertSee('step: 1', false);
    }

    public function test_a_missing_address_field_returns_to_the_shipping_step(): void
    {
        $this->addToBag();

        $this->guest()->from('/checkout')->post('/checkout', $this->fill(['address_line' => '']))
            ->assertRedirect('/checkout')->assertSessionHasErrors('address_line');

        $this->guest()->followingRedirects()->post('/checkout', $this->fill(['address_line' => '']))
            ->assertOk()->assertSee('step: 2', false);
    }

    public function test_chosen_options_travel_to_the_summary_and_the_order(): void
    {
        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant, 'quantity' => 1, 'options' => ['Colour' => 'Ivory']])->assertOk();

        $this->guest()->get('/checkout')->assertOk()->assertSee('Colour: Ivory');

        $this->guest()->post('/checkout', $this->fill())->assertRedirect();
        $this->assertSame(['Colour' => 'Ivory'], Order::query()->latest('id')->firstOrFail()->items->first()->options);
    }
}
