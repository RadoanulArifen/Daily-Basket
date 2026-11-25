<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryReason;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\OrderNotifier;
use App\Services\Payments\StripeGateway;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Refunds: some or all items and/or delivery, optionally restocked.
 * Card payments are refunded in Stripe first, so the money and the records never disagree.
 */
class RefundService
{
    public function __construct(private InventoryService $inventory, private OrderNotifier $notifier, private StripeGateway $stripe) {}

    /** What the refund page suggests for these items + delivery. */
    public function suggest(Order $order, array $quantities, string $shipping): Money
    {
        $items = Money::zero($order->currency);
        foreach ($order->items as $item) {
            $qty = (int) ($quantities[$item->id] ?? 0);
            if ($qty > 0) {
                $items = $items->add($item->unit_price->multiply($qty));
            }
        }
        $items = Money::ofMinor((int) round($items->minor * $order->paidShareOfItems()), $order->currency);
        $total = $items->add(Money::of($shipping === '' ? '0' : $shipping, $order->currency));

        return $total->minor > $order->refundable()->minor ? $order->refundable() : $total;
    }

    /** Delivery that can still be refunded. */
    public function refundableShipping(Order $order): Money
    {
        $refunded = $order->refunds->reduce(fn (Money $s, Refund $r) => $s->add($r->shipping_amount), Money::zero($order->currency));
        $left = $order->shipping_total->subtract($refunded);

        return $left->minor > 0 ? $left : Money::zero($order->currency);
    }

    /**
     * @param  array<int, int>  $quantities  order item id => quantity
     * @param  bool  $viaGateway  false = only record a refund already made elsewhere (e.g. in the Stripe dashboard)
     */
    public function refund(Order $order, array $quantities, bool $restock, string $shipping, string $amount, ?string $reason, bool $notify = true, ?Admin $admin = null, bool $viaGateway = true): Refund
    {
        if (! in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
            throw ValidationException::withMessages(['amount' => __('admin.orders.errors.not_paid')]);
        }

        $order->loadMissing(['items.variant', 'refunds']);
        $quantities = array_filter(array_map('intval', $quantities), fn ($q) => $q > 0);
        $refundedQty = $order->refundedQuantities();

        foreach ($quantities as $itemId => $qty) {
            $item = $order->items->firstWhere('id', $itemId);
            if (! $item || $qty > $item->quantity - ($refundedQty[$itemId] ?? 0)) {
                throw ValidationException::withMessages(['items' => __('admin.refund.errors.too_many')]);
            }
        }

        $shippingMoney = Money::of($shipping === '' ? '0' : $shipping, $order->currency);
        $amountMoney = Money::of($amount === '' ? '0' : $amount, $order->currency);

        if ($shippingMoney->minor > $this->refundableShipping($order)->minor) {
            throw ValidationException::withMessages(['shipping' => __('admin.refund.errors.shipping')]);
        }
        if ($amountMoney->minor > $order->refundable()->minor) {
            throw ValidationException::withMessages(['amount' => __('admin.refund.errors.too_much', ['max' => price_admin($order->refundable())])]);
        }
        if ($amountMoney->isZero() && ! $quantities) {
            throw ValidationException::withMessages(['amount' => __('admin.refund.errors.nothing')]);
        }

        // Card payments: refund in Stripe first. If Stripe says no, nothing is recorded.
        $reference = null;
        if ($viaGateway && ! $amountMoney->isZero() && $order->payment_method !== 'cod') {
            $payment = $order->payments()->where('status', 'paid')->whereNotNull('reference')->latest('id')->first();
            if (! $payment) {
                throw ValidationException::withMessages(['amount' => __('admin.refund.errors.no_payment')]);
            }
            try {
                $key = 'refund-'.$order->id.'-'.($order->refunds->count() + 1).'-'.$amountMoney->minor;
                $reference = $this->stripe->refund($payment->reference, $amountMoney->minor, $key);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages(['amount' => __('admin.refund.errors.gateway', ['message' => $e->getMessage()])]);
            }
        }

        $refund = DB::transaction(function () use ($order, $quantities, $restock, $shippingMoney, $amountMoney, $reason, $admin, $reference) {
            $refund = $order->refunds()->create([
                'amount' => $amountMoney, 'shipping_amount' => $shippingMoney, 'reason' => $reason ?: null,
                'gateway_reference' => $reference, 'admin_id' => $admin?->id,
            ]);

            foreach ($quantities as $itemId => $qty) {
                $variant = $order->items->firstWhere('id', $itemId)->variant;
                $put = $restock && $variant && $variant->track_inventory && ! $variant->trashed();
                if ($put) {
                    $this->inventory->adjust($variant, $qty, InventoryReason::Returned, $order->number, $admin);
                }
                $refund->items()->create(['order_item_id' => $itemId, 'quantity' => $qty, 'restocked' => $put]);
            }

            $refunded = $order->refunded_total->add($amountMoney);
            $full = $refunded->minor >= $order->grand_total->minor;
            $order->forceFill([
                'refunded_total' => $refunded,
                'payment_status' => $full ? PaymentStatus::Refunded : ($refunded->isZero() ? $order->payment_status : PaymentStatus::PartiallyRefunded),
            ])->save();
            if ($full) {
                $order->payments()->where('status', 'paid')->update(['status' => 'refunded']);
            }

            // Items refunded before shipping no longer need to ship.
            $order->unsetRelation('refunds');
            $fulfillments = app(FulfillmentService::class);
            if ($order->fulfillment_status === FulfillmentStatus::Partial && array_sum($fulfillments->unfulfilled($order)) === 0) {
                $order->forceFill(['fulfillment_status' => FulfillmentStatus::Shipped])->save();
            }

            app(OrderService::class)->log($order, 'refunded', trim(price_admin($amountMoney).' '.($reason ? '· '.$reason : '')), $admin);

            return $refund;
        });

        if ($notify && ! $amountMoney->isZero()) {
            $this->notifier->send($order->fresh(), 'refunded');
        }

        return $refund;
    }
}
