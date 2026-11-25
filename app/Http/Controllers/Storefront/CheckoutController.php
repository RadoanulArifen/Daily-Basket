<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Checkout\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Mail\NewOrderForStore;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderService;
use App\Services\Orders\TotalsCalculator;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\StripeGateway;
use App\Services\Shipping\ShippingService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cart, ShippingService $shipping, PaymentManager $payments, TotalsCalculator $totals): View|RedirectResponse
    {
        app(\App\Services\Analytics\Funnel::class)->record('checkout');

        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        $summary = $cart->summary();

        if ($summary->isEmpty() || $summary->notices) {
            // The bag page explains what changed.
            return redirect(store_route('cart'));
        }

        $user = $request->user('web');
        $savedAddresses = $user?->addresses()->get() ?? collect();
        $address = $savedAddresses->first();
        $country = old('country', $address?->country ?: \App\Support\Countries::default());
        $state = old('state', $address?->state);
        $rates = $shipping->rates($summary->discountedSubtotal(), \App\Support\Countries::shippingRegion($country, $state));
        $shippingPrice = fn ($rate) => $summary->freeShipping() ? Money::zero($summary->subtotal->currency) : $rate->price;
        $gateways = $payments->available();
        $defaultRate = array_key_first($rates);
        $zero = Money::zero($summary->subtotal->currency);

        return view('storefront.checkout.show', [
            'summary' => $summary,
            'savedAddresses' => $savedAddresses,
            'rates' => $rates,
            'ratePrice' => $shippingPrice,
            'gateways' => $gateways,
            'defaults' => [
                'email' => $user?->email,
                'phone' => $address?->phone ?? $user?->phone,
                'name' => $address?->name ?? $user?->name,
                'country' => $country,
                'state' => $state,
                'city' => $address?->city,
                'address_line' => $address?->address_line,
                'building' => $address?->building,
            ],
            // Totals for every shipping × payment combination, so the summary updates instantly.
            'totalsMatrix' => collect($rates)->mapWithKeys(fn ($rate, $code) => [$code => collect($gateways)->mapWithKeys(function ($gateway, $g) use ($totals, $summary, $rate, $shippingPrice) {
                $ship = $shippingPrice($rate);
                $t = $totals->calculate($summary->subtotal, $ship, Money::of($gateway->fee(), $summary->subtotal->currency), $summary->totalDiscount());

                return [$g => ['shipping' => $ship->isZero() ? __('store.checkout.free') : price($ship), 'fee' => $t->fee->isZero() ? null : price($t->fee), 'tax' => price($t->tax), 'total' => price($t->grandTotal), 'shipping_whole' => $ship->isZero() ? __('store.checkout.free') : price_whole($ship), 'fee_whole' => $t->fee->isZero() ? null : price_whole($t->fee), 'tax_whole' => price_whole($t->tax), 'total_whole' => price_whole($t->grandTotal)]];
            })]),
            'initialTotals' => $totals->calculate($summary->subtotal, $defaultRate ? $shippingPrice($rates[$defaultRate]) : $zero, $zero, $summary->totalDiscount()),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder, PaymentManager $payments): RedirectResponse
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        // Ownership: the cart being checked out is this visitor's own — a signed-in customer's
        // cart (user_id) or this browser's guest cart (signed cookie, user_id null).
        $current = app(\App\Services\Cart\CartService::class)->current();
        abort_if($current && $current->user_id && $current->user_id !== auth('web')->id(), 403);

        $gateway = $payments->available()[$request->input('payment_method')] ?? null;
        if (! $gateway) {
            throw ValidationException::withMessages(['payment_method' => __('store.checkout.payment_unavailable')]);
        }

        try {
            $order = $placeOrder->handle($request->validated(), $gateway, $request->user('web'));
        } catch (ValidationException $e) {
            if (isset($e->errors()['cart'])) {
                return redirect(store_route('cart'))->withErrors($e->errors());
            }
            throw $e;
        }

        $this->sendEmails($order);

        try {
            $paymentUrl = $gateway->start($order);
        } catch (Throwable $e) {
            report($e);

            // The order is saved and its stock held; the customer can retry from the order page.
            return redirect(store_route('orders.show', ['token' => $order->access_token]))->with('payment_error', __('store.checkout.payment_start_failed'));
        }

        return $paymentUrl
            ? redirect()->away($paymentUrl)
            : redirect(store_route('orders.show', ['token' => $order->access_token]))->with('placed', true);
    }

    /** Stripe sends the customer back here after paying. */
    public function complete(Request $request, string $token, StripeGateway $stripe, OrderService $orders): RedirectResponse
    {
        $order = Order::query()->where('access_token', $token)->firstOrFail();
        $sessionId = (string) $request->query('session_id');

        if ($sessionId !== '' && $order->payments()->where('reference', $sessionId)->exists()) {
            try {
                $session = $stripe->retrieveSession($sessionId);
                if ($stripe->sessionPaysOrder($session, $order)) {
                    $orders->markPaid($order, $sessionId);
                }
            } catch (Throwable $e) {
                // The webhook will confirm it shortly; the order page shows "processing".
                report($e);
            }
        }

        return redirect(store_route('orders.show', ['token' => $order->access_token]))->with('placed', true);
    }

    private function guard(Request $request): ?RedirectResponse
    {
        if (! $request->user('web') && ! settings()->bool('checkout.guest_checkout', true)) {
            session()->put('url.intended', store_route('checkout'));

            return redirect(store_route('login'))->with('status', __('store.checkout.login_required'));
        }

        return null;
    }

    private function sendEmails(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new OrderPlaced($order));

            if ($storeEmail = settings()->get('general.store_email')) {
                Mail::to($storeEmail)->send(new NewOrderForStore($order));
            }
        } catch (Throwable $e) {
            // A mail problem must never lose an order.
            report($e);
        }
    }
}
