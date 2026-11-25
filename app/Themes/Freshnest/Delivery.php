<?php

namespace App\Themes\Freshnest;

use App\Services\Settings\SettingsService;
use App\Support\Money;

/**
 * The delivery figures the FreshNest design prints around the store ("Free delivery on
 * orders over $50", the cart drawer's progress bar and delivery line). All of them come
 * from Settings → Shipping, so changing the rate or threshold there changes the storefront.
 */
class Delivery
{
    public function __construct(private SettingsService $settings) {}

    /** The free-delivery threshold, or null when free delivery is off. */
    public function freeThreshold(): ?Money
    {
        $threshold = $this->settings->get('shipping.free_threshold');

        if (! $this->settings->bool('shipping.free_enabled') || $threshold === null || $threshold === '') {
            return null;
        }

        return Money::of((string) $threshold, config('store.base_currency'));
    }

    /**
     * What standard delivery costs for this basket, or null when the store does not use the
     * flat rate (e.g. Aramex rates only): the price is then worked out at checkout.
     */
    public function feeFor(Money $subtotal): ?Money
    {
        if (! $this->settings->bool('shipping.flat_enabled', true)) {
            return null;
        }

        $free = $this->freeThreshold();
        if ($subtotal->isZero() || ($free && ! $free->isGreaterThan($subtotal))) {
            return Money::zero($subtotal->currency);
        }

        return Money::of((string) $this->settings->get('shipping.flat_rate', '0.00'), $subtotal->currency);
    }

    /** How much more to spend for free delivery (null when already free or not offered). */
    public function toFree(Money $subtotal): ?Money
    {
        $free = $this->freeThreshold();

        return $free && $free->isGreaterThan($subtotal) ? $free->subtract($subtotal) : null;
    }

    /** Progress towards free delivery, 0–100. */
    public function freePercent(Money $subtotal): int
    {
        $free = $this->freeThreshold();
        if (! $free || $free->isZero()) {
            return 100;
        }

        return (int) min(100, round($subtotal->minor / $free->minor * 100));
    }
}
