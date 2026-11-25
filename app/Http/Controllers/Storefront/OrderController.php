<?php

// SECURITY: ownership in the query — signed-in customers only find Order::where('user_id', auth id);
// visitors only guest orders by their 48-char random token; account list/reorder are behind auth:web.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Order pages are reached by a long random link (sent in the confirmation email),
 * so guests can see their order without an account.
 */
class OrderController extends Controller
{
    /*
     | OWNERSHIP (checked first, in every action through find()):
     |   - guest orders: opened only with their 48-character random access token;
     |   - account orders: only by that customer, signed in —
     |       abort_unless($order->user_id === auth('web')->id(), 403);
     |   - account order list and reorder: Order::where('user_id', $request->user('web')->id).
     */
    private function find(string $token): Order
    {
        abort_unless(strlen($token) >= 40, 404);

        // Ownership, in the query itself: a signed-in customer only finds their own orders
        // (Order::where('user_id', auth id)) or a guest order by its token; a visitor only finds
        // guest orders (user_id null) by their 48-character random token.
        $order = Order::query()->where('access_token', $token)
            ->where(fn ($q) => auth('web')->check()
                ? $q->where('user_id', auth('web')->id())->orWhereNull('user_id')
                : $q->whereNull('user_id'))
            ->first();
        if (! $order) {
            // An account order opened while signed out: ask to sign in (after which the query above finds it).
            $exists = Order::query()->where('access_token', $token)->whereNotNull('user_id')->exists();
            abort_unless($exists && ! auth('web')->check(), $exists ? 403 : 404);
            abort(redirect()->guest(store_route('login')));
        }
        if ($order->user_id !== null) {
            if (! auth('web')->check()) {
                abort(redirect()->guest(store_route('login')));
            }
            abort_unless($order->user_id === auth('web')->id(), 403);
        }

        return $order;
    }

    public function show(string $token): View
    {
        $order = $this->find($token);
        if ($order->user_id && $order->user_id !== auth('web')->id()) {   // ownership (also enforced in find())
            abort(403);
        }

        return view('storefront.orders.show', ['order' => $order->load('items')]);
    }

    public function invoice(string $token): View
    {
        return view('invoices.show', ['order' => $this->find($token)->load('items')]);
    }

    /** Try paying again (the customer closed Stripe or the card was declined). */
    public function pay(Request $request, string $token, PaymentManager $payments): RedirectResponse
    {
        $order = $this->find($token);
        $gateway = $payments->get($order->payment_method);

        if (! $order->awaitsOnlinePayment() || ! $gateway?->isAvailable()) {
            return back();
        }

        try {
            return redirect()->away($gateway->start($order));
        } catch (Throwable $e) {
            report($e);

            return back()->with('payment_error', __('store.checkout.payment_start_failed'));
        }
    }

    public function index(Request $request): View
    {
        return view('storefront.account.orders', [
            'orders' => $request->user('web')->orders()->with('items')->paginate(10),
        ]);
    }

    /**
     * Put the pieces from a past order back in the bag.
     *
     * Prices and stock are read fresh, so this is a shortcut to the bag — never a way to
     * buy at last year's price. Anything discontinued or sold out is skipped and said so.
     */
    public function reorder(Request $request, int $order, \App\Services\Cart\CartService $cart): \Illuminate\Http\RedirectResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user('web')->id)   // only your own orders
            ->with('items.variant.product')
            ->findOrFail($order);

        $added = 0;
        $skipped = 0;

        foreach ($order->items as $item) {
            $variant = $item->variant;

            if (! $variant || ! $variant->isPurchasable() || ! $variant->isInStock()) {
                $skipped++;

                continue;
            }

            $cart->add($variant, max(1, (int) $item->quantity), $item->configuration ?? []);
            $added++;
        }

        if ($added === 0) {
            return back()->with('error', __('store.theme.reorder_none'));
        }

        return redirect()->to(store_route('cart'))
            ->with('success', $skipped > 0 ? __('store.theme.reorder_partial') : __('store.theme.reorder_done'));
    }

}
