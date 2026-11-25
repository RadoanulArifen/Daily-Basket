<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;

/** One row of the cart, with prices read fresh from the variant (base currency). */
final class CartLine
{
    public function __construct(
        public readonly CartItem $item,
        public readonly ProductVariant $variant,
        public readonly Product $product,
        public readonly Money $unitPrice,
        public readonly Money $lineTotal,
        public readonly ?int $available,
    ) {}
}
