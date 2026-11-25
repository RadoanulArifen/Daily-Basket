<?php

namespace App\Services\Shipping;

use App\Support\Money;

class ShippingService
{
    /** @return list<ShippingCarrier> */
    public function carriers(): array
    {
        return [app(FlatRateCarrier::class), app(AramexCarrier::class)];
    }

    /** @return array<string, ShippingRate> keyed by code */
    public function rates(Money $subtotal, string $emirate, int $weightGrams = 0): array
    {
        $rates = [];

        foreach ($this->carriers() as $carrier) {
            if ($carrier->isEnabled()) {
                foreach ($carrier->rates($subtotal, $emirate, $weightGrams) as $rate) {
                    $rates[$rate->code] = $rate;
                }
            }
        }

        return $rates;
    }
}
