<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\AdminController;
use App\Support\Search;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends AdminController
{
    public const TABS = ['all', 'unfulfilled', 'unpaid', 'open', 'cancelled'];

    public const SORTS = ['newest', 'oldest', 'total_desc', 'total_asc'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'q' => ['nullable', 'string', 'max:100'],
            'payment' => ['nullable', Rule::enum(PaymentStatus::class)],
            'fulfillment' => ['nullable', Rule::enum(FulfillmentStatus::class)],
            'method' => ['nullable', Rule::in(['cod', 'card'])],
            'date' => ['nullable', Rule::in(['today', '7', '30', '90'])],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ]);
        $tab = $filters['tab'] ?? 'all';
        $tz = config('store.display_timezone');

        $orders = $this->tab(Order::query(), $tab)
            ->withCount('items')
            ->withSum('items as units', 'quantity')
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.mb_strtolower($term).'%';
                $q->where(fn ($w) => Search::like($w, 'number', $term)
                    ->orWhereRaw('lower(email) like ?', [$like])
                    ->orWhereRaw('lower(ship_name) like ?', [$like])
                    ->when($this->phoneDigits($term), fn ($p, $digits) => $p->orWhere('phone', 'like', '%'.$digits.'%')));
            })
            ->when($filters['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($filters['fulfillment'] ?? null, fn ($q, $v) => $q->where('fulfillment_status', $v))
            ->when($filters['method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v === 'card' ? '!=' : '=', 'cod'))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->where('placed_at', '>=', $v === 'today'
                ? now($tz)->startOfDay()->utc() : now()->subDays((int) $v)))
            ->tap(fn (Builder $q) => match ($filters['sort'] ?? 'newest') {
                'oldest' => $q->oldest('id'),
                'total_desc' => $q->orderByDesc('grand_total'),
                'total_asc' => $q->orderBy('grand_total'),
                default => $q->latest('id'),
            })
            ->paginate(50)
            ->withQueryString();

        return view('admin.sales.orders.index', [
            'orders' => $orders,
            'tab' => $tab,
            'filters' => $filters,
            'counts' => collect(['unfulfilled', 'unpaid'])->mapWithKeys(fn ($t) => [$t => $this->tab(Order::query(), $t)->count()]),
        ]);
    }

    /** Bulk actions from the orders list. */
    public function bulk(Request $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['fulfill'])],
        ]);

        $done = 0;
        foreach (Order::query()->whereKey($data['ids'])->get() as $order) {
            if ($order->status === OrderStatus::Open && $order->fulfillment_status === FulfillmentStatus::Unfulfilled && ! $order->awaitsOnlinePayment()) {
                $orders->ship($order, null, null, $request->user('admin'));
                $done++;
            }
        }

        return back()->with('success', trans_choice('admin.orders.bulk_fulfilled', $done, ['count' => $done]));
    }

    public function show(Order $order): View
    {
        return view('admin.sales.orders.show', [
            'order' => $order->load(['items.variant', 'payments', 'events.admin', 'user', 'returns', 'fulfillments.items.orderItem', 'refunds']),
        ]);
    }

    public function invoice(Order $order): View
    {
        return view('invoices.show', ['order' => $order->load('items')]);
    }

    public function markPaid(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->markPaid($order, admin: $request->user('admin'));

        return back()->with('success', __('admin.orders.marked_paid'));
    }

    /**
     * Email the customer their order again: the confirmation, or a link to the invoice.
     * Useful when a confirmation bounced, the address was wrong, or the customer asks.
     */
    public function email(Request $request, Order $order): RedirectResponse
    {
        $what = $request->validate(['what' => ['required', \Illuminate\Validation\Rule::in(['confirmation', 'invoice', 'payment'])]])['what'];

        if (blank($order->email)) {
            return back()->with('error', __('admin.orders.email_no_address'));
        }

        try {
            if ($what === 'payment') {
                // A link to the order, where "Pay now" is waiting. Only useful while unpaid.
                Mail::to($order->email)->send(new \App\Mail\CustomerMessage(
                    event: 'payment',
                    vars: ['{name}' => \Illuminate\Support\Str::before($order->ship_name, ' '), '{number}' => $order->number],
                    details: [
                        __('store.order.number_short') => $order->number,
                        __('store.cart.total') => price_order($order->grand_total, $order),
                    ],
                    actionUrl: store_route('orders.show', ['token' => $order->access_token]),
                    actionText: __('store.order.pay_link'),
                    lang: $order->locale,
                ));
            } elseif ($what === 'confirmation') {
                Mail::to($order->email)->send(new \App\Mail\OrderPlaced($order));
            } else {
                Mail::to($order->email)->send(new \App\Mail\CustomerMessage(
                    event: 'invoice',
                    vars: ['{name}' => \Illuminate\Support\Str::before($order->ship_name, ' '), '{number}' => $order->number],
                    details: [
                        __('store.order.number_short') => $order->number,
                        __('store.cart.total') => price_order($order->grand_total, $order),
                    ],
                    actionUrl: store_route('orders.invoice', ['token' => $order->access_token]),
                    actionText: __('store.order.invoice'),
                    lang: $order->locale,
                ));
            }
        } catch (\Throwable $e) {
            return back()->with('error', __('admin.orders.email_failed', ['error' => $e->getMessage()]));
        }

        // Recorded on the order's timeline, so the team can see it was re-sent.
        \App\Models\OrderEvent::create([
            'order_id' => $order->id,
            'type' => 'email_sent',
            'message' => __("admin.orders.email_event_{$what}", ['email' => $order->email]),
            'admin_id' => $request->user('admin')?->id,
        ]);

        return back()->with('success', __('admin.orders.email_sent', ['email' => $order->email]));
    }

    public function ship(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $orders->ship($order, $data['carrier'] ?? null, $data['tracking_number'] ?? null, $request->user('admin'));

        return back()->with('success', __('admin.orders.marked_shipped'));
    }

    public function deliver(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->deliver($order, $request->user('admin'));

        return back()->with('success', __('admin.orders.marked_delivered'));
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'restock' => ['nullable', 'boolean'],
        ]);

        $orders->cancel($order, $data['reason'] ?? null, $request->user('admin'), (bool) ($data['restock'] ?? true));

        return back()->with('success', __('admin.orders.cancelled'));
    }

    public function refund(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $orders->markRefunded($order, $request->user('admin'));

        return back()->with('success', __('admin.orders.marked_refunded'));
    }

    public function note(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $orders->log($order, 'note', $data['note'], $request->user('admin'));

        return back()->with('success', __('admin.orders.note_added'));
    }

    /** "050 123 4567" and "+971 50 123 4567" both find +971501234567. */
    private function phoneDigits(string $term): ?string
    {
        $digits = ltrim(preg_replace('/\D/', '', $term), '0');

        return strlen($digits) >= 5 ? $digits : null;
    }

    private function tab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'unfulfilled' => $query->where('status', OrderStatus::Open)->where('fulfillment_status', FulfillmentStatus::Unfulfilled)
                ->where(fn ($q) => $q->where('payment_method', 'cod')->orWhere('payment_status', PaymentStatus::Paid)),
            'unpaid' => $query->where('status', '!=', OrderStatus::Cancelled)->where('payment_status', PaymentStatus::Pending),
            'open' => $query->where('status', OrderStatus::Open),
            'cancelled' => $query->where('status', OrderStatus::Cancelled),
            default => $query,
        };
    }
}
