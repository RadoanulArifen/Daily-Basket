<?php

namespace Tests\Feature\Checkout;

use App\Models\Admin;
use App\Models\Order;

class AdminOrdersTest extends CheckoutTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addToBag($this->makeProduct('Woven Cushion', [], [['price' => '145.00', 'stock_quantity' => 5]])->variants()->first(), 2);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $this->order = Order::sole();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_lists_search_and_detail(): void
    {
        $this->get('/admin/orders')->assertOk()->assertSee($this->order->number)->assertSee('Mariam Al Nuaimi');
        $this->get('/admin/orders?tab=unfulfilled')->assertSee($this->order->number);
        $this->get('/admin/orders?tab=cancelled')->assertDontSee($this->order->number);
        $this->get('/admin/orders?q=mariam')->assertSee($this->order->number);
        $this->get('/admin/orders?q=0501234567')->assertSee($this->order->number);
        $this->get('/admin/orders/'.$this->order->id)->assertOk()->assertSee('Street 12, Villa 4')->assertSee('Please call first')->assertSee('Order placed');
        $this->get('/admin/orders/'.$this->order->id.'/invoice')->assertOk()->assertSee('Tax invoice');
        $this->get('/admin')->assertOk()->assertSee('1 order to fulfill');
    }

    public function test_cod_order_lifecycle_to_completed(): void
    {
        $id = $this->order->id;
        $this->post("/admin/orders/{$id}/ship", ['carrier' => 'Aramex', 'tracking_number' => 'AX123'])->assertSessionHas('success');
        $this->post("/admin/orders/{$id}/paid")->assertSessionHas('success');
        $this->post("/admin/orders/{$id}/deliver")->assertSessionHas('success');

        $order = $this->order->fresh();
        $this->assertSame('completed', $order->status->value);
        $this->assertSame('paid', $order->payment_status->value);
        $this->assertSame('delivered', $order->fulfillment_status->value);
        $this->assertSame('AX123', $order->tracking_number);
        // Shipments are logged in detail: fulfilled → paid → shipment delivered → order delivered.
        $this->assertSame(['delivered', 'fulfillment_delivered', 'paid', 'fulfilled', 'placed'], $order->events()->pluck('type')->all());

        $this->get('/orders/'.$order->access_token)->assertSee('Delivered')->assertSee('AX123');

        // Delivered orders can't be cancelled.
        $this->post("/admin/orders/{$id}/cancel")->assertSessionHasErrors('order');
    }

    public function test_cancel_restocks_and_voids_payment(): void
    {
        $variant = $this->order->items()->first()->variant;
        $this->assertSame(3, $variant->stock_quantity);

        $this->post("/admin/orders/{$this->order->id}/cancel", ['reason' => 'Customer asked', 'restock' => '1'])->assertSessionHas('success');

        $order = $this->order->fresh();
        $this->assertTrue($order->isCancelled());
        $this->assertSame('voided', $order->payment_status->value);
        $this->assertSame(5, $variant->fresh()->stock_quantity);

        // Cancelling twice doesn't restock twice.
        $this->post("/admin/orders/{$this->order->id}/cancel", ['restock' => '1']);
        $this->assertSame(5, $variant->fresh()->stock_quantity);
        $this->post("/admin/orders/{$this->order->id}/paid")->assertSessionHasErrors('order');
    }

    public function test_refund_needs_a_paid_order_and_notes_are_kept(): void
    {
        $id = $this->order->id;
        $this->post("/admin/orders/{$id}/refund")->assertSessionHasErrors('order');

        $this->post("/admin/orders/{$id}/paid");
        $this->post("/admin/orders/{$id}/refund")->assertSessionHas('success');
        $this->assertSame('refunded', $this->order->fresh()->payment_status->value);

        $this->post("/admin/orders/{$id}/note", ['note' => 'Refunded in cash'])->assertSessionHas('success');
        $this->get("/admin/orders/{$id}")->assertSee('Refunded in cash');
    }

    public function test_shipping_and_payment_settings(): void
    {
        $this->get('/admin/settings/shipping')->assertOk();
        $this->put('/admin/settings/shipping', [
            'flat_enabled' => '1', 'flat_name' => ['en' => 'Courier', 'ar' => 'مندوب'], 'flat_rate' => '30.00',
            'free_enabled' => '0', 'free_threshold' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame('30.00', settings()->get('shipping.flat_rate'));

        $this->get('/admin/settings/payments')->assertOk()->assertSee('Stripe keys are not set')->assertSee('/webhooks/stripe');
        $this->put('/admin/settings/payments', ['cod_enabled' => '1', 'stripe_enabled' => '1'])->assertSessionHasErrors('stripe_enabled');
        $this->put('/admin/settings/payments', ['cod_enabled' => '0', 'stripe_enabled' => '0'])->assertSessionHasErrors('cod_enabled');
        $this->put('/admin/settings/payments', ['cod_enabled' => '1', 'cod_fee' => '5'])->assertSessionHasNoErrors();

        $this->get('/admin')->assertOk();
    }
}
