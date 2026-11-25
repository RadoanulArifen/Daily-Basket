<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every change to an order's status goes through here, and each one is written
 * to the order's timeline.
 */
class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private \App\Services\Notifications\OrderNotifier $notifier,
    ) {}

    public function log(Order $order, string $type, ?string $message = null, ?Admin $admin = null): void
    {
        OrderEvent::create(['order_id' => $order->id, 'type' => $type, 'message' => $message, 'admin_id' => $admin?->id]);
    }

    /** Safe to call twice (Stripe may confirm the same payment by redirect and by webhook). */
    public function markPaid(Order $order, ?string $reference = null, ?Admin $admin = null): bool
    {
        return DB::transaction(function () use ($order, $reference, $admin) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PaymentStatus::Paid) {
                return false;
            }

            if ($locked->isCancelled() && $locked->payment_method === 'cod') {
                throw ValidationException::withMessages(['order' => __('admin.orders.errors.cancelled')]);
            }

            $locked->forceFill(['payment_status' => PaymentStatus::Paid, 'paid_at' => now()])->save();
            $locked->payments()->where('status', 'pending')->when($reference, fn ($q) => $q->where('reference', $reference))
                ->update(['status' => 'paid']);

            if ($locked->payment_method === 'cod' && ! $locked->payments()->exists()) {
                $locked->payments()->create(['gateway' => 'cod', 'status' => 'paid', 'amount' => $locked->grand_total, 'currency' => $locked->currency]);
            }

            $this->log($locked, 'paid', $reference, $admin);
            $this->completeIfDone($locked);
            $order->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }

    /** Ship everything that hasn't shipped yet, as one shipment (see FulfillmentService for partial shipments). */
    public function ship(Order $order, ?string $carrier, ?string $tracking, ?Admin $admin = null): void
    {
        $this->assertOpen($order);
        app(FulfillmentService::class)->fulfill($order->load('items'), null, $carrier, $tracking, true, $admin);
    }

    /** Everything arrived: ships anything left, then marks every shipment delivered. */
    public function deliver(Order $order, ?Admin $admin = null): void
    {
        $this->assertOpen($order);
        $fulfillments = app(FulfillmentService::class);

        if (array_sum($fulfillments->unfulfilled($order->load('items'))) > 0) {
            $fulfillments->fulfill($order, null, $order->carrier, $order->tracking_number, false, $admin);
        }
        foreach ($order->fulfillments()->whereNull('delivered_at')->get() as $fulfillment) {
            $fulfillments->markDelivered($fulfillment->setRelation('order', $order), $admin);
        }
    }

    /**
     * Cancel and put the items back in stock. A paid card order must then be
     * refunded in Stripe; the admin marks it refunded here afterwards.
     */
    public function cancel(Order $order, ?string $reason = null, ?Admin $admin = null, bool $restock = true, bool $notify = true): void
    {
        $cancelled = DB::transaction(function () use ($order, $reason, $admin, $restock) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                return false;
            }

            if ($locked->fulfillment_status === FulfillmentStatus::Delivered) {
                throw ValidationException::withMessages(['order' => __('admin.orders.errors.delivered')]);
            }

            if ($restock) {
                foreach ($locked->items()->with('variant')->get() as $item) {
                    if ($item->variant && $item->variant->track_inventory) {
                        $this->inventory->adjust($item->variant, $item->quantity, InventoryReason::OrderCancelled, $locked->number, $admin);
                    }
                }
            }

            $locked->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'payment_status' => $locked->payment_status === PaymentStatus::Pending ? PaymentStatus::Voided : $locked->payment_status,
            ])->save();
            $locked->payments()->where('status', 'pending')->update(['status' => 'voided']);

            // The discount code can be used again.
            if ($locked->discount_code && \App\Models\DiscountUsage::query()->where('order_id', $locked->id)->delete()) {
                \App\Models\Discount::query()->where('code', $locked->discount_code)->where('used_count', '>', 0)->decrement('used_count');
            }

            $this->log($locked, 'cancelled', $reason, $admin);
            $order->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($cancelled && $notify) {
            $this->notifier->send($order, 'cancelled');
        }
    }

    /** Record a full refund made outside this site (e.g. in the Stripe dashboard). */
    public function markRefunded(Order $order, ?Admin $admin = null): void
    {
        if (! in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
            throw ValidationException::withMessages(['order' => __('admin.orders.errors.not_paid')]);
        }

        $refunds = app(RefundService::class);
        $order->load(['items', 'refunds']);
        $refunded = $order->refundedQuantities();
        $remaining = $order->items->mapWithKeys(fn ($i) => [$i->id => $i->quantity - ($refunded[$i->id] ?? 0)])->filter()->all();

        $refunds->refund($order, $remaining, false, $refunds->refundableShipping($order)->toDecimal(), $order->refundable()->toDecimal(), null, false, $admin, viaGateway: false);
    }

    private function completeIfDone(Order $order): void
    {
        if ($order->payment_status === PaymentStatus::Paid && $order->fulfillment_status === FulfillmentStatus::Delivered && $order->status === OrderStatus::Open) {
            $order->forceFill(['status' => OrderStatus::Completed])->save();
        }
    }

    private function assertOpen(Order $order): void
    {
        if ($order->isCancelled()) {
            throw ValidationException::withMessages(['order' => __('admin.orders.errors.cancelled')]);
        }
    }
}
