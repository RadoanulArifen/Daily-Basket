<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class StripeTest extends CheckoutTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_123', 'services.stripe.webhook_secret' => 'whsec_test']);
        settings()->setMany('payments', ['stripe_enabled' => true]);
        app(SettingsService::class)->flush();
    }

    private function placeCardOrder(): Order
    {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'])]);

        $this->addToBag($this->makeProduct('Dallah', [], [['price' => '240.00', 'stock_quantity' => 5]])->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['payment_method' => 'stripe']))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');

        return Order::sole();
    }

    private function sessionFor(Order $order, array $overrides = []): array
    {
        return array_merge([
            'id' => 'cs_test_1', 'payment_status' => 'paid', 'client_reference_id' => (string) $order->id,
            'amount_total' => $order->grand_total->minor, 'currency' => 'aed',
        ], $overrides);
    }

    public function test_card_checkout_sends_the_exact_total_to_stripe_and_holds_stock(): void
    {
        $order = $this->placeCardOrder();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.stripe.com/v1/checkout/sessions'
            && $r['line_items'][0]['price_data']['unit_amount'] === 24000   // 240.00, free delivery over 200
            && $r['line_items'][0]['price_data']['currency'] === 'aed'
            && $r['client_reference_id'] === (string) $order->id);

        $this->assertSame('pending', $order->payment_status->value);
        $this->assertSame(4, $order->items()->first()->variant->stock_quantity);
        $this->assertTrue($order->payments()->where('reference', 'cs_test_1')->exists());

        $this->get('/orders/'.$order->access_token.'?payment=cancelled')->assertSee('Payment wasn’t completed')->assertSee('Pay AED');
    }

    public function test_returning_from_stripe_marks_the_order_paid_after_checking_with_stripe(): void
    {
        $order = $this->placeCardOrder();
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->sessionFor($order))]);

        $this->get('/checkout/complete/'.$order->access_token.'?session_id=cs_test_1')->assertRedirect('/orders/'.$order->access_token);

        $this->assertSame('paid', $order->fresh()->payment_status->value);
        $this->assertSame('paid', $order->payments()->first()->status);
    }

    public function test_a_session_that_does_not_match_the_order_is_not_trusted(): void
    {
        $order = $this->placeCardOrder();
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->sessionFor($order, ['amount_total' => 100]))]);

        $this->get('/checkout/complete/'.$order->access_token.'?session_id=cs_test_1');
        $this->get('/checkout/complete/'.$order->access_token.'?session_id=cs_someone_else');

        $this->assertSame('pending', $order->fresh()->payment_status->value);
    }

    public function test_webhook_marks_paid_once_and_rejects_bad_signatures(): void
    {
        $order = $this->placeCardOrder();
        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => $this->sessionFor($order)]]);
        $t = time();
        $good = 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, 'whsec_test');

        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't='.$t.',v1=forged', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->payment_status->value);

        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $good, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $good, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status->value);
        $this->assertSame(1, $order->events()->where('type', 'paid')->count());
    }

    public function test_unpaid_card_orders_are_released_after_the_time_limit(): void
    {
        $order = $this->placeCardOrder();
        $variant = $order->items()->first()->variant;

        Artisan::call('orders:release-unpaid');
        $this->assertFalse($order->fresh()->isCancelled()); // still within the hour

        $this->travel(61)->minutes();
        Artisan::call('orders:release-unpaid');

        $order->refresh();
        $this->assertTrue($order->isCancelled());
        $this->assertSame('voided', $order->payment_status->value);
        $this->assertSame(5, $variant->fresh()->stock_quantity);
    }

    public function test_stripe_failure_keeps_the_order_and_offers_a_retry(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'Invalid API key']], 401)]);
        $this->addToBag($this->makeProduct('Dallah')->variants()->first());

        $this->cookieRequest()->post('/checkout', $this->checkoutData(['payment_method' => 'stripe']));
        $order = Order::sole();

        $this->get('/orders/'.$order->access_token)->assertOk()->assertSee('Pay AED');
    }
}
