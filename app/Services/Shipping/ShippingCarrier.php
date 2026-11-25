<?php

namespace App\Services\Shipping;

use App\Support\Money;

/**
 * A source of delivery options. Add a carrier by implementing this and
 * listing it in ShippingService::carriers().
 */
interface ShippingCarrier
{
    public function isEnabled(): bool;

    /**
     * @param  Money  $subtotal  base currency
     * @return list<ShippingRate>
     */
    public function rates(Money $subtotal, string $emirate, int $weightGrams): array;
}
