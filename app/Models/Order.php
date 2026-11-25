<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Casts\MoneyCast;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Order extends Model
{
    use Auditable, HasTranslations;

    public array $translatable = ['shipping_method_name'];

    protected $guarded = ['id'];

    protected $attributes = [
        'number' => null,
        'user_id' => null,
        'status' => 'open',
        'payment_status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
        'phone' => null,
        'marketing_opt_in' => false,
        'ship_phone' => null,
        'ship_building' => null,
        'ship_country' => 'AE',
        'shipping_total' => '0.00',
        'payment_fee' => '0.00',
        'discount_total' => '0.00',
        'discount_code' => null,
        'promotion_total' => '0.00',
        'refunded_total' => '0.00',
        'promotion_names' => null,
        'tax_total' => '0.00',
        'prices_include_tax' => true,
        'tax_rate' => '0.00',
        'tax_label' => null,
        'display_rate' => '1.000000',
        'carrier' => null,
        'tracking_number' => null,
        'customer_note' => null,
        'paid_at' => null,
        'shipped_at' => null,
        'delivered_at' => null,
        'cancelled_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'subtotal' => MoneyCast::class,
            'shipping_total' => MoneyCast::class,
            'payment_fee' => MoneyCast::class,
            'discount_total' => MoneyCast::class,
            'promotion_total' => MoneyCast::class,
            'refunded_total' => MoneyCast::class,
            'promotion_names' => 'array',
            'tax_total' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
            'prices_include_tax' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Orders that count as sales: not cancelled or refunded, and either cash on delivery
     * or actually paid (an unfinished card payment is not a sale yet).
     */
    public function scopeCountsAsSale(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled)
            ->where('payment_status', '!=', PaymentStatus::Refunded)
            ->where(fn ($q) => $q->where('payment_method', 'cod')->orWhere('payment_status', PaymentStatus::Paid));
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /** A card order that can still be paid (the customer left Stripe before paying). */
    public function awaitsOnlinePayment(): bool
    {
        return $this->payment_method !== 'cod'
            && $this->payment_status === PaymentStatus::Pending
            && ! $this->isCancelled();
    }

    /**
     * One word for customers, combining the three statuses:
     * awaiting_payment | confirmed | shipped | delivered | cancelled.
     */
    public function stage(): string
    {
        return match (true) {
            $this->isCancelled() => 'cancelled',
            $this->fulfillment_status === FulfillmentStatus::Delivered => 'delivered',
            in_array($this->fulfillment_status, [FulfillmentStatus::Shipped, FulfillmentStatus::Partial], true) => 'shipped',
            $this->awaitsOnlinePayment() => 'awaiting_payment',
            default => 'confirmed',
        };
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** Deals applied to this order, e.g. [["label" => "Buy 2, get 1 free", "amount" => "95.00"]]. */
    public function dealLines(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return array_map(fn ($d) => ['label' => $d[$locale] ?? $d['en'] ?? '', 'amount' => \App\Support\Money::of((string) $d['amount'], $this->currency)], (array) $this->promotion_names);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest('id');
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class)->orderBy('sequence');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest('id');
    }

    /** @return array<int, int> order item id => quantity already shipped */
    public function fulfilledQuantities(): array
    {
        return FulfillmentItem::query()->whereIn('fulfillment_id', $this->fulfillments()->select('id'))
            ->selectRaw('order_item_id, sum(quantity) as qty')->groupBy('order_item_id')->pluck('qty', 'order_item_id')
            ->map(fn ($q) => (int) $q)->all();
    }

    /** @return array<int, int> order item id => quantity already refunded */
    public function refundedQuantities(): array
    {
        return RefundItem::query()->whereIn('refund_id', $this->refunds()->select('id'))
            ->selectRaw('order_item_id, sum(quantity) as qty')->groupBy('order_item_id')->pluck('qty', 'order_item_id')
            ->map(fn ($q) => (int) $q)->all();
    }

    /** Money that can still be refunded. */
    public function refundable(): \App\Support\Money
    {
        $left = $this->grand_total->subtract($this->refunded_total);

        return $left->minor > 0 ? $left : \App\Support\Money::zero($this->currency);
    }

    /** Share of the items' price actually paid after deals and codes (for suggested refund amounts). */
    public function paidShareOfItems(): float
    {
        $subtotal = $this->subtotal->minor;

        return $subtotal > 0 ? max(0, ($subtotal - $this->discount_total->minor - $this->promotion_total->minor) / $subtotal) : 1.0;
    }

    /** "City, State, Country" of the delivery address. */
    public function shipRegionLine(): string
    {
        return \App\Support\Countries::line($this->ship_city, $this->ship_state, $this->ship_country, $this->ship_emirate);
    }
}
