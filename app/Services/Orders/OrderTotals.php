<?php

namespace App\Services\Orders;

use App\Support\Money;

final class OrderTotals
{
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $shipping,
        public readonly Money $fee,
        public readonly Money $tax,
        public readonly Money $grandTotal,
        public readonly bool $pricesIncludeTax,
        public readonly string $taxRate,
        public readonly ?string $taxLabel,
        public readonly ?Money $discount = null,
    ) {}
}
