<?php

namespace App\Services\Returns;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryReason;
use App\Mail\ReturnUpdate;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Services\Inventory\InventoryService;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Returns and exchanges: who may ask, and each step after that. */
class ReturnService
{
    public function __construct(private OrderService $orders, private InventoryService $inventory) {}

    public function windowDays(): int
    {
        return max(0, (int) settings()->get('returns.window_days', 14));
    }

    public function enabled(): bool
    {
        return settings()->bool('returns.enabled', true);
    }

    /** Delivered, not cancelled, within the return window, and something left to return. */
    public function canRequest(Order $order): bool
    {
        return $this->enabled()
            && ! $order->isCancelled()
            && $order->fulfillment_status === FulfillmentStatus::Delivered
            && $order->delivered_at?->gt(now()->subDays($this->windowDays()))
            && array_sum($this->returnable($order)) > 0;
    }

    /** @return array<int, int> order item id => quantity that can still be returned */
    public function returnable(Order $order): array
    {
        $already = ReturnItem::query()
            ->whereHas('returnRequest', fn ($q) => $q->where('order_id', $order->id)->where('status', '!=', 'rejected'))
            ->selectRaw('order_item_id, sum(quantity) as qty')->groupBy('order_item_id')->pluck('qty', 'order_item_id');

        return $order->items->mapWithKeys(fn ($item) => [$item->id => max(0, $item->quantity - (int) ($already[$item->id] ?? 0))])->all();
    }

    /** @param  array<int, int>  $quantities  order item id => quantity */
    public function request(Order $order, array $quantities, string $resolution, string $reason, ?string $note): ReturnRequest
    {
        if (! $this->canRequest($order)) {
            throw ValidationException::withMessages(['items' => __('store.returns.not_allowed')]);
        }

        $allowed = $this->returnable($order);
        $quantities = array_filter(array_map('intval', $quantities), fn ($q) => $q > 0);

        if (! $quantities) {
            throw ValidationException::withMessages(['items' => __('store.returns.choose_items')]);
        }

        foreach ($quantities as $itemId => $qty) {
            if (! isset($allowed[$itemId]) || $qty > $allowed[$itemId]) {
                throw ValidationException::withMessages(['items' => __('store.returns.too_many')]);
            }
        }

        $return = DB::transaction(function () use ($order, $quantities, $resolution, $reason, $note) {
            $return = ReturnRequest::create(['order_id' => $order->id, 'resolution' => $resolution, 'reason' => $reason, 'customer_note' => $note]);
            $return->forceFill(['number' => 'R-'.(1000 + $return->id)])->save();
            foreach ($quantities as $itemId => $qty) {
                $return->items()->create(['order_item_id' => $itemId, 'quantity' => $qty]);
            }
            $this->orders->log($order, 'return_requested', $return->number);

            return $return;
        });

        $this->notify($return, 'requested');

        return $return;
    }

    public function approve(ReturnRequest $return, ?string $note, ?Admin $admin): void
    {
        $this->move($return, ['requested'], 'approved', $note, $admin);
    }

    public function reject(ReturnRequest $return, ?string $note, ?Admin $admin): void
    {
        $this->move($return, ['requested', 'approved'], 'rejected', $note, $admin);
    }

    /** The parcel arrived back; optionally put the items back in stock. */
    public function receive(ReturnRequest $return, bool $restock, ?Admin $admin): void
    {
        DB::transaction(function () use ($return, $restock, $admin) {
            $this->move($return, ['approved'], 'received', null, $admin, notify: false);

            if ($restock && ! $return->restocked) {
                foreach ($return->items()->with('orderItem.variant')->get() as $item) {
                    $variant = $item->orderItem?->variant;
                    if ($variant && $variant->track_inventory && ! $variant->trashed()) {
                        $this->inventory->adjust($variant, $item->quantity, InventoryReason::Returned, $return->number, $admin);
                    }
                }
                $return->forceFill(['restocked' => true])->save();
            }
        });

        $this->notify($return, 'received');
    }

    /**
     * The return is done: the exchange was sent, or the money went back.
     *
     * With $refund the money is paid back here (card refund through Stripe, or recorded for
     * a cash order) instead of the team having to remember to do it separately. Refunding is
     * never automatic — the admin ticks it — because it moves real money.
     */
    public function complete(ReturnRequest $return, ?string $note, ?Admin $admin, bool $refund = false): void
    {
        if ($refund) {
            $this->refundFor($return, $admin);
        }

        $this->move($return, ['received'], 'completed', $note, $admin);
    }

    /** What this return is worth: the returned lines at what the customer actually paid. */
    public function refundable(ReturnRequest $return): \App\Support\Money
    {
        $quantities = $return->items->pluck('quantity', 'order_item_id')->all();

        return app(\App\Services\Orders\RefundService::class)->suggest($return->order, $quantities, '0');
    }

    private function refundFor(ReturnRequest $return, ?Admin $admin): void
    {
        $order = $return->order;
        $amount = $this->refundable($return);

        if ($amount->minor <= 0 || $order->refundable()->minor <= 0) {
            return;   // nothing left to give back
        }

        app(\App\Services\Orders\RefundService::class)->refund(
            order: $order,
            quantities: $return->items->pluck('quantity', 'order_item_id')->all(),
            restock: false,   // the pieces came back into stock when they were received
            shipping: '0',
            amount: $amount->toDecimal(),
            reason: __('admin.returns.refund_reason', ['number' => $return->number]),
            notify: true,
            admin: $admin,
        );
    }

    private function move(ReturnRequest $return, array $from, string $to, ?string $note, ?Admin $admin, bool $notify = true): void
    {
        if (! in_array($return->status, $from, true)) {
            throw ValidationException::withMessages(['status' => __('admin.returns.wrong_step')]);
        }

        $return->update(['status' => $to, 'admin_note' => $note ?: $return->admin_note]);
        $this->orders->log($return->order, 'return_'.$to, trim($return->number.' '.($note ?? '')), $admin);

        if ($notify) {
            $this->notify($return, $to);
        }
    }

    private function notify(ReturnRequest $return, string $step): void
    {
        try {
            Mail::to($return->order->email)->send(new ReturnUpdate($return->fresh(['order', 'items.orderItem']), $step));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
