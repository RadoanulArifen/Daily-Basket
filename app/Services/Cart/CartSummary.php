<?php

namespace App\Services\Cart;

use App\Support\Money;

final class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     * @param  list<string>  $notices  messages about items removed or reduced since last time
     */
    public function __construct(
        public readonly array $lines,
        public readonly Money $subtotal,
        public readonly int $count,
        public readonly array $notices = [],
        public readonly ?\App\Models\Discount $discount = null,
        public readonly ?Money $discountAmount = null,
        public readonly ?Money $promotionAmount = null,
        /** @var list<array{name: array, amount: Money}> */
        public readonly array $promotions = [],
    ) {}

    /** Deals + discount code together. */
    public function totalDiscount(): Money
    {
        $total = Money::zero($this->subtotal->currency);
        foreach ([$this->promotionAmount, $this->discountAmount] as $amount) {
            if ($amount) {
                $total = $total->add($amount);
            }
        }

        return $total;
    }

    /** Items after the discount code: what delivery thresholds and VAT are worked out on. */
    public function discountedSubtotal(): Money
    {
        return $this->subtotal->subtract($this->totalDiscount());
    }

    public function freeShipping(): bool
    {
        return $this->discount?->type === 'free_shipping';
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }
}
