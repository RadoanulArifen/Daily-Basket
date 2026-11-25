<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Settings\SettingsService;
use App\Support\Localization;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Card payments on Stripe's hosted Checkout page, so card details never touch this server.
 * Talks to Stripe's REST API with Laravel's HTTP client (no SDK needed for these three calls).
 */
class StripeGateway implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function __construct(private SettingsService $settings) {}

    public function code(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return __('store.checkout.card');
    }

    public function description(): ?string
    {
        return __('store.checkout.card_text');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.stripe.secret'), 'sk_test_');
    }

    public function isAvailable(): bool
    {
        return $this->settings->bool('payments.stripe_enabled') && $this->isConfigured();
    }

    public function fee(): string
    {
        return '0.00';
    }

    public function start(Order $order): string
    {
        $localization = app(Localization::class);
        $minutes = max(30, (int) config('store.unpaid_order_minutes'));

        $response = $this->client()->asForm()->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'customer_email' => $order->email,
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
            'payment_intent_data' => ['metadata' => ['order_id' => $order->id, 'order_number' => $order->number]],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => $order->grand_total->minor,
                    'product_data' => ['name' => __('store.checkout.stripe_item', ['number' => $order->number], $order->locale)],
                ],
            ]],
            'locale' => 'auto',
            'expires_at' => now()->addMinutes($minutes)->timestamp,
            'success_url' => $localization->route('checkout.complete', ['token' => $order->access_token], $order->locale).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $localization->route('orders.show', ['token' => $order->access_token], $order->locale).'?payment=cancelled',
        ]);

        if ($response->failed() || ! $response->json('url')) {
            throw new RuntimeException('Stripe Checkout Session could not be created: '.$response->json('error.message', $response->status()));
        }

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'reference' => $response->json('id'),
        ]);

        return $response->json('url');
    }

    /** Fetch a Checkout Session to confirm what Stripe says about it. */
    public function retrieveSession(string $sessionId): array
    {
        $response = $this->client()->get(self::API.'/checkout/sessions/'.urlencode($sessionId));

        if ($response->failed()) {
            throw new RuntimeException('Stripe session lookup failed: '.$response->status());
        }

        return $response->json();
    }

    /** True if this session is paid in full for this order. */
    public function sessionPaysOrder(array $session, Order $order): bool
    {
        return ($session['payment_status'] ?? null) === 'paid'
            && (string) ($session['client_reference_id'] ?? '') === (string) $order->id
            && (int) ($session['amount_total'] ?? -1) === $order->grand_total->minor
            && strtoupper((string) ($session['currency'] ?? '')) === $order->currency;
    }

    /**
     * Check the Stripe-Signature header of a webhook request.
     * https://docs.stripe.com/webhooks#verify-manually
     */
    public function verifyWebhook(string $payload, ?string $header, int $tolerance = 300): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '' || ! $header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$key][] = $value;
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);
        if ($timestamp === 0 || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Refund part or all of a paid Checkout Session. Returns Stripe's refund id.
     * https://docs.stripe.com/api/refunds/create
     */
    public function refund(string $sessionId, int $amountMinor, string $idempotencyKey): string
    {
        $intent = $this->retrieveSession($sessionId)['payment_intent'] ?? null;
        if (! $intent) {
            throw new RuntimeException('This payment has no Stripe payment to refund.');
        }

        // The idempotency key makes Stripe process this refund at most once, even if the
        // request is sent again after a dropped connection.
        $response = $this->client()->withHeaders(['Idempotency-Key' => $idempotencyKey])->asForm()->post(self::API.'/refunds', [
            'payment_intent' => is_array($intent) ? $intent['id'] : $intent,
            'amount' => $amountMinor,
        ]);

        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error.message') ?? 'Stripe refund failed: '.$response->status()));
        }

        return (string) $response->json('id');
    }

    /**
     * "Test connection": a harmless read-only request that proves the secret key works.
     *
     * @return array{ok: bool, message: string, live: bool}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => __('admin.credentials.stripe_missing'), 'live' => false];
        }

        $response = $this->client()->get(self::API.'/balance');

        return $response->successful()
            ? ['ok' => true, 'message' => __($this->isTestMode() ? 'admin.credentials.stripe_ok_test' : 'admin.credentials.stripe_ok_live'), 'live' => ! $this->isTestMode()]
            : ['ok' => false, 'message' => (string) ($response->json('error.message') ?? 'HTTP '.$response->status()), 'live' => false];
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('services.stripe.secret'), '')
            ->withHeaders(['Stripe-Version' => '2024-06-20'])
            ->timeout(20)
            // Retry only when the connection failed or Stripe had a temporary problem — never
            // when Stripe answered "no" (4xx), which would just repeat the refusal.
            ->retry(2, 500, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException
                || ($e instanceof \Illuminate\Http\Client\RequestException && $e->response->serverError()), throw: false);
    }
}
