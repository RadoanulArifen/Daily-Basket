<?php

namespace App\Services\Shipping;

use App\Services\Settings\SettingsService;
use App\Support\Money;

/** Standard delivery at a fixed price, free above a threshold. Set in Admin → Settings → Shipping. */
class FlatRateCarrier implements ShippingCarrier
{
    public function __construct(private SettingsService $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->bool('shipping.flat_enabled', true);
    }

    public function rates(Money $subtotal, string $emirate, int $weightGrams): array
    {
        $currency = $subtotal->currency;
        $price = Money::of((string) $this->settings->get('shipping.flat_rate', '0.00'), $currency);
        $threshold = $this->settings->get('shipping.free_threshold');

        if ($this->settings->bool('shipping.free_enabled') && $threshold !== null && $threshold !== ''
            && ! Money::of((string) $threshold, $currency)->isGreaterThan($subtotal)) {
            $price = Money::zero($currency);
        }

        return [new ShippingRate(
            'standard',
            array_filter((array) $this->settings->get('shipping.flat_name', ['en' => 'Standard delivery'])),
            $price,
            array_filter((array) $this->settings->get('shipping.flat_description', [])) ?: null,
        )];
    }
}
