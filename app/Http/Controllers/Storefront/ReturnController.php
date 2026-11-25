<?php

// SECURITY: a return is created only for an order the visitor owns — loaded with
// Order::where('user_id', auth id) (or a guest order by its random token); there is no return-by-id page.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Returns\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnController extends Controller
{
    /*
     | OWNERSHIP: a return can only be requested for an order the visitor may open —
     | guest orders by their 48-character random token, account orders only by that
     | customer, signed in (abort_unless($order->user_id === auth('web')->id(), 403)).
     */
    private function order(string $token): Order
    {
        abort_unless(strlen($token) >= 40, 404);

        // Ownership in the query: signed-in customers only find their own orders (or guest orders
        // by token); visitors only guest orders by token.
        $order = Order::query()->where('access_token', $token)
            ->where(fn ($q) => auth('web')->check()
                ? $q->where('user_id', auth('web')->id())->orWhereNull('user_id')
                : $q->whereNull('user_id'))
            ->with('items')->first();
        if (! $order) {
            $exists = Order::query()->where('access_token', $token)->whereNotNull('user_id')->exists();
            abort_unless($exists && ! auth('web')->check(), $exists ? 403 : 404);
            abort(redirect()->guest(store_route('login')));
        }
        // Ownership. Guest orders (user_id null) are opened with their 48-character random
        // token only. An order that belongs to a customer account can only be opened by that
        // customer, signed in: a visitor is sent to sign in, any other customer gets 403.
        if ($order->user_id !== null) {
            if (! auth('web')->check()) {
                abort(redirect()->guest(store_route('login')));
            }
            abort_unless($order->user_id === auth('web')->id(), 403);
        }

        return $order;
    }

    public function create(string $token, ReturnService $returns): View|RedirectResponse
    {
        $order = $this->order($token);
        abort_if($order->user_id && $order->user_id !== auth('web')->id(), 403);   // ownership

        if (! $returns->canRequest($order)) {
            return redirect(store_route('orders.show', ['token' => $token]));
        }

        return view('storefront.returns.create', ['order' => $order, 'returnable' => $returns->returnable($order), 'days' => $returns->windowDays()]);
    }

    public function store(Request $request, string $token, ReturnService $returns): RedirectResponse
    {
        $order = $this->order($token);
        abort_if($order->user_id && $order->user_id !== auth('web')->id(), 403);   // ownership
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:99'],
            'resolution' => ['required', Rule::in(['refund', 'exchange'])],
            'reason' => ['required', Rule::in(ReturnRequest::REASONS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['reason' => __('store.returns.reason'), 'resolution' => __('store.returns.resolution')]);

        $return = $returns->request($order, $data['items'], $data['resolution'], $data['reason'], $data['note'] ?? null);

        return redirect(store_route('orders.show', ['token' => $token]))->with('store_toast', __('store.returns.sent', ['number' => $return->number]));
    }

}
