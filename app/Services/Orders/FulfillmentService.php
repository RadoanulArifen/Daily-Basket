<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\Notifications\OrderNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Shipping some or all of an order's items, and marking shipments delivered. */
class FulfillmentService
{
    public function __construct(private OrderNotifier $notifier) {}

    /** @return array<int, int> order item id => quantity still to ship */
    public function unfulfilled(Order $order): array
    {
        $shipped = $order->fulfilledQuantities();
        $refunded = $order->refundedQuantities();

        return $order->items->mapWithKeys(fn ($item) => [
            $item->id => max(0, $item->quantity - ($shipped[$item->id] ?? 0) - ($refunded[$item->id] ?? 0)),
        ])->all();
    }

    /** @param  array<int, int>|null  $quantities  order item id => quantity; null ships everything left */
    public function fulfill(Order $order, ?array $quantities, ?string $carrier, ?string $tracking, bool $notify = true, ?Admin $admin = null): Fulfillment
    {
        if ($order->isCancelled()) {
            throw ValidationException::withMessages(['order' => __('admin.orders.errors.cancelled')]);
        }
        if ($order->awaitsOnlinePayment()) {
            throw ValidationException::withMessages(['order' => __('admin.fulfill.errors.unpaid')]);
        }

        $fulfillment = DB::transaction(function () use ($order, $quantities, $carrier, $tracking, $admin) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();
            $left = $this->unfulfilled($locked);
            $quantities = array_filter(array_map('intval', $quantities ?? $left), fn ($q) => $q > 0);

            if (! $quantities) {
                throw ValidationException::withMessages(['items' => __('admin.fulfill.errors.nothing')]);
            }
            foreach ($quantities as $itemId => $qty) {
                if (! array_key_exists($itemId, $left) || $qty > $left[$itemId]) {
                    throw ValidationException::withMessages(['items' => __('admin.fulfill.errors.too_many')]);
                }
            }

            $fulfillment = $locked->fulfillments()->create([
                'sequence' => ((int) $locked->fulfillments()->max('sequence')) + 1,
                'carrier' => $carrier ?: null,
                'tracking_number' => $tracking ?: null,
                'admin_id' => $admin?->id,
            ]);
            foreach ($quantities as $itemId => $qty) {
                $fulfillment->items()->create(['order_item_id' => $itemId, 'quantity' => $qty]);
            }

            $allShipped = array_sum($this->unfulfilled($locked)) === 0;
            $locked->forceFill([
                'fulfillment_status' => $allShipped ? FulfillmentStatus::Shipped : FulfillmentStatus::Partial,
                'carrier' => $carrier ?: $locked->carrier,          // latest shipment, shown to the customer
                'tracking_number' => $tracking ?: $locked->tracking_number,
                'shipped_at' => $locked->shipped_at ?? now(),
            ])->save();

            app(OrderService::class)->log($locked, 'fulfilled', trim(
                __('admin.fulfill.log', ['number' => '#'.$fulfillment->sequence, 'count' => array_sum($quantities)]).' '.trim(($carrier ?? '').' '.($tracking ?? ''))
            ), $admin);
            $order->setRawAttributes($locked->getAttributes(), true);

            return $fulfillment;
        });

        if ($notify) {
            $this->notifier->send($order, 'shipped');
        }

        return $fulfillment;
    }

    /** A shipment arrived. When everything has arrived, the order is delivered. */
    public function markDelivered(Fulfillment $fulfillment, ?Admin $admin = null): void
    {
        $order = $fulfillment->order;
        if ($fulfillment->delivered_at) {
            return;
        }

        $fulfillment->forceFill(['delivered_at' => now()])->save();
        app(OrderService::class)->log($order, 'fulfillment_delivered', '#'.$fulfillment->sequence, $admin);

        $everythingArrived = $order->fulfillments()->whereNull('delivered_at')->doesntExist()
            && array_sum($this->unfulfilled($order->load('items'))) === 0;

        if ($everythingArrived) {
            $order->forceFill(['fulfillment_status' => FulfillmentStatus::Delivered, 'delivered_at' => now()])->save();
            app(OrderService::class)->log($order, 'delivered', null, $admin);
            if ($order->payment_status === PaymentStatus::Paid && $order->status === OrderStatus::Open) {
                $order->forceFill(['status' => OrderStatus::Completed])->save();
            }
            $this->notifier->send($order, 'delivered');
        }
    }
}
