<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\Orders\FulfillmentService;
use App\Services\Orders\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The "Fulfill items" and "Refund" pages of an order. */
class OrderFlowController extends AdminController
{
    public function fulfillForm(Order $order, FulfillmentService $fulfillments): View|RedirectResponse
    {
        $order->load('items');
        $left = $fulfillments->unfulfilled($order);

        if ($order->isCancelled() || array_sum($left) === 0) {
            return redirect()->route('admin.orders.show', $order);
        }

        return view('admin.sales.orders.fulfill', ['order' => $order, 'left' => $left]);
    }

    public function fulfill(Request $request, Order $order, FulfillmentService $fulfillments): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'carrier' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $fulfillment = $fulfillments->fulfill($order->load('items'), $data['items'], $data['carrier'] ?? null, $data['tracking_number'] ?? null, $request->boolean('notify'), $request->user('admin'));

        return redirect()->route('admin.orders.show', $order)->with('success', __('admin.fulfill.done', ['number' => '#'.$fulfillment->sequence]));
    }

    public function delivered(Request $request, Fulfillment $fulfillment, FulfillmentService $fulfillments): RedirectResponse
    {
        $fulfillments->markDelivered($fulfillment, $request->user('admin'));

        return back()->with('success', __('admin.fulfill.delivered'));
    }

    public function refundForm(Order $order, RefundService $refunds): View|RedirectResponse
    {
        $order->load(['items', 'refunds']);

        if ($order->refundable()->isZero() && $order->items->sum('quantity') <= array_sum($order->refundedQuantities())) {
            return redirect()->route('admin.orders.show', $order);
        }

        return view('admin.sales.orders.refund', [
            'order' => $order,
            'refunded' => $order->refundedQuantities(),
            'shippingLeft' => $refunds->refundableShipping($order),
        ]);
    }

    public function refund(Request $request, Order $order, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'shipping' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $refund = $refunds->refund($order, $data['items'] ?? [], $request->boolean('restock'), (string) ($data['shipping'] ?? '0'),
            (string) $data['amount'], $data['reason'] ?? null, $request->boolean('notify'), $request->user('admin'));

        return redirect()->route('admin.orders.show', $order)->with('success', __('admin.refund.done', ['amount' => price_admin($refund->amount)]));
    }
}
