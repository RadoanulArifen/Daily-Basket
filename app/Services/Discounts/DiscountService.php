<?php

namespace App\Services\Discounts;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Support\Money;

/**
 * Discount codes: whether one can be used right now, and how much it takes off.
 * Used by the bag, the checkout page and PlaceOrder, so the rules live in one place.
 */
class DiscountService
{
    public function find(?string $code): ?Discount
    {
        $code = mb_strtoupper(trim((string) $code));

        return $code === '' ? null : Discount::query()->where('code', $code)->first();
    }

    /**
     * Why the code can't be used, or null if it can.
     * The per-customer limit needs an email, so it is only checked once one is known.
     */
    public function problem(?Discount $discount, Money $subtotal, ?string $email = null): ?string
    {
        if (! $discount || ! $discount->is_active) {
            return __('store.discount.invalid');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return __('store.discount.not_started');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return __('store.discount.expired');
        }

        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
            return __('store.discount.used_up');
        }

        if ($discount->min_subtotal && $discount->min_subtotal->isGreaterThan($subtotal)) {
            return __('store.discount.minimum', ['amount' => price($discount->min_subtotal)]);
        }

        if ($email && $discount->usage_limit_per_customer !== null) {
            $used = DiscountUsage::query()->where('discount_id', $discount->id)->where('email', mb_strtolower($email))->count();

            if ($used >= $discount->usage_limit_per_customer) {
                return __('store.discount.already_used');
            }
        }

        return null;
    }

    /** Amount taken off the items (never more than the items cost). Free delivery is handled by the caller. */
    public function itemsDiscount(Discount $discount, Money $subtotal): Money
    {
        $amount = match ($discount->type) {
            'percentage' => $subtotal->percentage((string) min(100, max(0, (float) $discount->value))),
            'fixed' => Money::of((string) $discount->value, $subtotal->currency),
            default => Money::zero($subtotal->currency),
        };

        return $amount->isGreaterThan($subtotal) ? $subtotal : $amount;
    }

    public function givesFreeShipping(?Discount $discount): bool
    {
        return $discount?->type === 'free_shipping';
    }
}
