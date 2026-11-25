<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderService;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stripe → this site: confirms payments even if the customer closes the browser
 * before returning. Set up in Stripe Dashboard → Developers → Webhooks with the events
 * checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.expired.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, OrderService $orders): Response
    {
        $payload = $request->getContent();

        if (! $stripe->verifyWebhook($payload, $request->header('Stripe-Signature'))) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $session = $event['data']['object'] ?? [];
        $payment = isset($session['id']) ? Payment::query()->where('reference', $session['id'])->with('order')->first() : null;
        $order = $payment?->order;

        if (! $order) {
            return response('Ignored', 200); // not one of ours, or already gone
        }

        match ($event['type'] ?? null) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $stripe->sessionPaysOrder($session, $order)
                ? $orders->markPaid($order, $session['id'])
                : null,
            'checkout.session.expired' => $payment->update(['status' => 'voided']),
            default => null,
        };

        return response('OK', 200);
    }
}
