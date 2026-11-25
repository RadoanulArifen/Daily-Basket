<?php

namespace Tests\Feature\Checkout;

use App\Enums\InventoryReason;
use App\Mail\NewOrderForStore;
use App\Mail\OrderPlaced;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Mail;

class CheckoutTest extends CheckoutTestCase
{
    public function test_checkout_needs_a_bag(): void
    {
        $this->get('/checkout')->assertRedirect('/cart');
    }

    public function test_a_guest_places_a_cash_on_delivery_order(): void
    {
        Mail::fake();
        $variant = $this->makeProduct('Oud Candle', [], [['price' => '95.00', 'stock_quantity' => 10, 'sku' => 'OUD-1']])->variants()->first();
        $this->addToBag($variant, 2);

        $this->cookieRequest()->get('/checkout')->assertOk()->assertSee('Oud Candle')->assertSee('Standard delivery')->assertSee('Cash on delivery');

        $response = $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::with('items')->sole();
        $response->assertRedirect('/orders/'.$order->access_token);

        // 2 × 95 = 190, under the AED 200 free-delivery threshold, so AED 25 delivery.
        $this->assertSame('190.00', $order->subtotal->toDecimal());
        $this->assertSame('25.00', $order->shipping_total->toDecimal());
        $this->assertSame('215.00', $order->grand_total->toDecimal());
        // VAT included: 215 × 5/105 = 10.24
        $this->assertSame('10.24', $order->tax_total->toDecimal());

        $this->assertSame('SH-'.(1000 + $order->id), $order->number);
        $this->assertSame('mariam@example.com', $order->email);
        $this->assertSame('+971501234567', $order->phone);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->payment_status->value);
        $this->assertSame('Oud Candle', $order->items[0]->getTranslation('name', 'en'));
        $this->assertSame('OUD-1', $order->items[0]->sku);

        // Stock taken and logged, bag emptied.
        $this->assertSame(8, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'change' => -2, 'reason' => InventoryReason::Sold->value, 'note' => $order->number]);
        $this->assertSame(0, CartItem::count());

        Mail::assertQueued(OrderPlaced::class, fn ($m) => $m->hasTo('mariam@example.com'));
        Mail::assertQueued(NewOrderForStore::class, fn ($m) => $m->hasTo('owner@example.com'));

        $this->withSession(['placed' => true])->get('/orders/'.$order->access_token)->assertOk()
            ->assertSee('Thank you, Mariam')->assertSee($order->number)->assertSee("AED\u{00A0}215.00");
    }

    public function test_free_delivery_over_the_threshold_and_cod_fee(): void
    {
        settings()->setMany('payments', ['cod_fee' => '10.00']);
        app(SettingsService::class)->flush();
        $variant = $this->makeProduct('Throw', [], [['price' => '320.00']])->variants()->first();
        $this->addToBag($variant);

        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::sole();

        $this->assertSame('0.00', $order->shipping_total->toDecimal());
        $this->assertSame('10.00', $order->payment_fee->toDecimal());
        $this->assertSame('330.00', $order->grand_total->toDecimal());
    }

    public function test_prices_excluding_vat_add_it_on_top(): void
    {
        settings()->setMany('tax', ['prices_include_tax' => false]);
        app(SettingsService::class)->flush();
        $variant = $this->makeProduct('Throw', [], [['price' => '100.00']])->variants()->first();
        $this->addToBag($variant);

        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::sole();

        // (100 + 25 delivery) × 5% = 6.25
        $this->assertSame('6.25', $order->tax_total->toDecimal());
        $this->assertSame('131.25', $order->grand_total->toDecimal());
        $this->assertFalse($order->prices_include_tax);
    }

    public function test_validation_and_unknown_methods(): void
    {
        $variant = $this->makeProduct('Throw')->variants()->first();
        $this->addToBag($variant);

        $this->cookieRequest()->post('/checkout', $this->checkoutData(['email' => 'nope', 'country' => 'XX', 'phone' => '12']))
            ->assertSessionHasErrors(['email', 'country', 'phone']);
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['payment_method' => 'stripe']))
            ->assertSessionHasErrors('payment_method'); // Stripe is off
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['shipping_method' => 'teleport']))
            ->assertSessionHasErrors('shipping_method');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $variant->fresh()->stock_quantity);
    }

    public function test_stock_that_ran_out_after_adding_blocks_the_order(): void
    {
        $variant = $this->makeProduct('Last One', [], [['stock_quantity' => 2]])->variants()->first();
        $this->addToBag($variant, 2);
        $variant->forceFill(['stock_quantity' => 1])->save(); // someone else bought one

        $this->cookieRequest()->post('/checkout', $this->checkoutData())->assertRedirect('/cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $variant->fresh()->stock_quantity);
    }

    public function test_a_price_change_is_charged_at_the_current_price(): void
    {
        $variant = $this->makeProduct('Bowl', [], [['price' => '130.00']])->variants()->first();
        $this->addToBag($variant);
        $variant->update(['price' => '150.00']);

        $this->cookieRequest()->post('/checkout', $this->checkoutData());

        $this->assertSame('150.00', Order::sole()->subtotal->toDecimal());
    }

    public function test_signed_in_customer_gets_the_order_on_their_account_and_address_saved(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeProduct('Cups')->variants()->first();
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);

        $this->actingAs($user)->post('/checkout', $this->checkoutData(['email' => $user->email]))->assertRedirect();
        $order = Order::sole();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Al Barsha', $user->addresses()->first()->city);
        $this->actingAs($user)->get('/account/orders')->assertOk()->assertSee($order->number);
        $this->actingAs($user)->get('/account')->assertSee($order->number)->assertSee('Al Barsha');

        // Next time the form is pre-filled.
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);
        $this->actingAs($user)->get('/checkout')->assertSee('value="Street 12, Villa 4"', false);
    }

    public function test_guest_checkout_can_be_switched_off(): void
    {
        settings()->setMany('checkout', ['guest_checkout' => false]);
        app(SettingsService::class)->flush();
        $this->addToBag($this->makeProduct('Throw')->variants()->first());

        $this->cookieRequest()->get('/checkout')->assertRedirect('/login');
        $this->cookieRequest()->post('/checkout', $this->checkoutData())->assertRedirect('/login');
        $this->assertSame(0, Order::count());
    }

    public function test_order_pages_need_the_private_link(): void
    {
        $this->addToBag($this->makeProduct('Throw')->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $order = Order::sole();

        $this->get('/orders/'.$order->number)->assertNotFound();
        $this->get('/orders/'.substr($order->access_token, 0, 20))->assertNotFound();
        $this->get('/orders/'.$order->access_token.'/invoice')->assertOk()->assertSee('Tax invoice')->assertSee('5.95'); // (100 + 25) × 5/105
    }

    public function test_arabic_checkout_and_order_email_language(): void
    {
        Mail::fake();
        $this->addToBag($this->makeProduct('Throw', ['ar' => 'غطاء'])->variants()->first());

        $this->cookieRequest()->get('/ar/checkout')->assertOk()->assertSee('إتمام الطلب')->assertSee('دبي');
        $this->cookieRequest()->post('/ar/checkout', $this->checkoutData())->assertRedirect();

        $order = Order::sole();
        $this->assertSame('ar', $order->locale);
        Mail::assertQueued(OrderPlaced::class, fn ($m) => $m->locale === 'ar');
        $this->get('/ar/orders/'.$order->access_token)->assertOk()->assertSee('غطاء');
    }
}
