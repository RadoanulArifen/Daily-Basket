<?php

namespace Tests\Feature\AdminUi;

use App\Mail\OrderPlaced;
use App\Mail\OrderStatusUpdate;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Checkout\CheckoutTestCase;

class OrderFlowTest extends CheckoutTestCase
{
    private Order $order;

    private function twoItemOrder(): Order
    {
        $this->addToBag($this->makeProduct('Throw', [], [['price' => '100.00', 'stock_quantity' => 10, 'sku' => 'TH-1']])->variants()->first(), 2);
        $this->addToBag($this->makeProduct('Cushion', [], [['price' => '50.00', 'stock_quantity' => 10, 'sku' => 'CU-1']])->variants()->first(), 1);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());

        return Order::with('items.variant')->sole();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = $this->twoItemOrder();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    private function item(string $sku)
    {
        return $this->order->items->firstWhere('sku', $sku);
    }

    public function test_partial_fulfilment_then_the_rest_then_delivery(): void
    {
        Mail::fake();
        $throw = $this->item('TH-1');
        $cushion = $this->item('CU-1');

        $this->get("/admin/orders/{$this->order->id}")->assertSee('Unfulfilled (3)')->assertSee('Fulfill items');
        $this->get("/admin/orders/{$this->order->id}/fulfill")->assertOk()->assertSee('Tracking number');

        // Ship one throw now.
        $this->post("/admin/orders/{$this->order->id}/fulfillments", ['items' => [$throw->id => 1, $cushion->id => 0], 'carrier' => 'Aramex', 'tracking_number' => 'AX1', 'notify' => '1'])
            ->assertRedirect("/admin/orders/{$this->order->id}");
        $this->assertSame('partial', $this->order->fresh()->fulfillment_status->value);
        Mail::assertQueued(OrderStatusUpdate::class, 1);

        $this->get("/admin/orders/{$this->order->id}")->assertSee('Partially fulfilled')->assertSee('Unfulfilled (2)')->assertSee('#1')->assertSee('AX1');

        // Too many is refused.
        $this->post("/admin/orders/{$this->order->id}/fulfillments", ['items' => [$throw->id => 2]])->assertSessionHasErrors('items');

        // The rest, without emailing.
        $this->post("/admin/orders/{$this->order->id}/fulfillments", ['items' => [$throw->id => 1, $cushion->id => 1], 'carrier' => 'Own delivery']);
        $order = $this->order->fresh();
        $this->assertSame('shipped', $order->fulfillment_status->value);
        $this->assertCount(2, $order->fulfillments);
        Mail::assertQueued(OrderStatusUpdate::class, 1);

        // Deliver both shipments; the order completes once paid.
        [$first, $second] = $order->fulfillments->all();
        $this->post("/admin/fulfillments/{$first->id}/delivered");
        $this->assertSame('shipped', $order->fresh()->fulfillment_status->value);
        $this->post("/admin/fulfillments/{$second->id}/delivered");
        $this->assertSame('delivered', $order->fresh()->fulfillment_status->value);
        $this->post("/admin/orders/{$order->id}/paid");
        $this->assertSame('completed', $order->fresh()->status->value);
        $this->assertTrue($order->events()->where('type', 'fulfilled')->count() === 2);
    }

    public function test_partial_refund_with_restock_then_the_rest(): void
    {
        Mail::fake();
        $this->post("/admin/orders/{$this->order->id}/paid");
        $throw = $this->item('TH-1');
        $this->assertSame(8, $throw->variant->fresh()->stock_quantity);

        // Nothing to refund on an unpaid order is handled on the page; here it's paid (250 + free delivery).
        $this->get("/admin/orders/{$this->order->id}/refunds/new")->assertOk()->assertSee('Available to refund');

        $this->post("/admin/orders/{$this->order->id}/refunds", ['items' => [$throw->id => 1], 'restock' => '1', 'amount' => '999.00'])->assertSessionHasErrors('amount');
        $this->post("/admin/orders/{$this->order->id}/refunds", ['items' => [$throw->id => 1], 'restock' => '1', 'amount' => '100.00', 'reason' => 'Damaged', 'notify' => '1'])
            ->assertSessionHas('success');

        $order = $this->order->fresh();
        $this->assertSame('partially_refunded', $order->payment_status->value);
        $this->assertSame('100.00', $order->refunded_total->toDecimal());
        $this->assertSame(9, $throw->variant->fresh()->stock_quantity);
        Mail::assertQueued(OrderStatusUpdate::class, fn ($m) => $m->event === 'refunded');
        $this->get("/admin/orders/{$order->id}")->assertSee('Partially refunded')->assertSee('Net payment')->assertSee("AED\u{00A0}150.00");

        // Refunding the throw that was refunded before shipping means only 2 items still need to ship.
        $this->assertSame(2, array_sum(app(\App\Services\Orders\FulfillmentService::class)->unfulfilled($order->load('items'))));

        // The rest, without restocking.
        $this->post("/admin/orders/{$order->id}/refunds", ['items' => [$throw->id => 1, $this->item('CU-1')->id => 1], 'amount' => '150.00']);
        $this->assertSame('refunded', $order->fresh()->payment_status->value);
        $this->assertSame(9, $throw->variant->fresh()->stock_quantity);
        $this->post("/admin/orders/{$order->id}/refunds", ['amount' => '1.00'])->assertSessionHasErrors('amount');
    }

    public function test_suggested_refund_accounts_for_discounts(): void
    {
        $this->order->forceFill(['discount_total' => '25.00', 'payment_status' => 'paid'])->save();   // 10% off the 250 items
        $suggest = app(\App\Services\Orders\RefundService::class)->suggest($this->order->fresh(), [$this->item('TH-1')->id => 1], '0');
        $this->assertSame('90.00', $suggest->toDecimal());
    }

    public function test_card_refunds_go_through_stripe_first(): void
    {
        config(['services.stripe.secret' => 'sk_test_x']);
        $this->order->forceFill(['payment_method' => 'stripe', 'payment_status' => 'paid'])->save();
        Payment::create(['order_id' => $this->order->id, 'gateway' => 'stripe', 'amount' => $this->order->grand_total, 'currency' => 'AED', 'status' => 'paid', 'reference' => 'cs_test_1']);

        Http::fake([
            'api.stripe.com/v1/checkout/sessions/*' => Http::response(['id' => 'cs_test_1', 'payment_intent' => 'pi_123']),
            'api.stripe.com/v1/refunds' => Http::sequence()->push(['error' => ['message' => 'Charge already refunded']], 400)->push(['id' => 're_789']),
        ]);

        // Stripe refuses: nothing is recorded.
        $this->post("/admin/orders/{$this->order->id}/refunds", ['amount' => '50.00'])->assertSessionHasErrors('amount');
        $this->assertSame(0, $this->order->refunds()->count());

        $this->post("/admin/orders/{$this->order->id}/refunds", ['amount' => '50.00'])->assertSessionHas('success');
        $this->assertSame('re_789', $this->order->refunds()->sole()->gateway_reference);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/refunds') && (int) $r['amount'] === 5000 && $r['payment_intent'] === 'pi_123' && $r->hasHeader('Idempotency-Key'));

        // "Mark as refunded" records the rest without calling Stripe again.
        Http::fake();
        $this->post("/admin/orders/{$this->order->id}/refund")->assertSessionHas('success');
        $this->assertSame('refunded', $this->order->fresh()->payment_status->value);
        Http::assertNothingSent();
    }

    public function test_admins_create_orders_that_work_like_checkout(): void
    {
        Mail::fake();
        $cups = $this->makeProduct('Gahwa Cups', [], [['price' => '110.00', 'stock_quantity' => 5, 'sku' => 'GC-6']])->variants()->first();
        $customer = User::factory()->create(['email' => 'hessa@example.com']);

        $this->get('/admin/orders/create')->assertOk()->assertSee('Create order');
        $this->getJson('/admin/orders/create/variants?q=gc-6')->assertJsonPath('variants.0.id', $cups->id);
        $this->getJson('/admin/orders/create/customers?q=hessa')->assertJsonPath('customers.0.id', $customer->id);
        $this->getJson('/admin/orders/create/rates?emirate=dubai&lines['.$cups->id.']=2')->assertJsonPath('rates.0.code', 'standard');

        $data = ['lines' => [$cups->id => 2], 'customer_id' => $customer->id, 'email' => 'hessa@example.com', 'phone' => '050 111 2222',
            'name' => 'Hessa Rashid', 'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => 'Villa 9', 'shipping_method' => 'standard',
            'payment' => 'paid', 'locale' => 'ar', 'notify' => '1'];
        $this->post('/admin/orders/create', ['lines' => []] + $data)->assertSessionHasErrors('lines');
        $carts = \App\Models\Cart::count();
        $this->post('/admin/orders/create', $data)->assertRedirect();
        $this->assertSame($carts, \App\Models\Cart::count()); // the temporary bag is gone

        $order = Order::latest('id')->first();
        $this->assertSame('manual', $order->payment_method);
        $this->assertSame('paid', $order->payment_status->value);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('ar', $order->locale);
        $this->assertSame('220.00', $order->subtotal->toDecimal());
        $this->assertSame(3, $cups->fresh()->stock_quantity);
        $this->assertTrue($order->events()->where('type', 'created_by_admin')->exists());
        Mail::assertQueued(OrderPlaced::class, fn ($m) => $m->hasTo('hessa@example.com'));

        // Cash on delivery, no email, more than in stock refused.
        $this->post('/admin/orders/create', ['lines' => [$cups->id => 9], 'payment' => 'cod', 'notify' => '0'] + $data)->assertSessionHasErrors();
        $this->post('/admin/orders/create', ['lines' => [$cups->id => 1], 'payment' => 'cod', 'notify' => '0'] + $data);
        $this->assertSame('pending', Order::latest('id')->first()->payment_status->value);
        $this->assertSame('cod', Order::latest('id')->first()->payment_method);
    }
}
