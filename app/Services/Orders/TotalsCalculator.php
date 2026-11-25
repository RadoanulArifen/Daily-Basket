<?php

namespace App\Services\Orders;

use App\Services\Settings\SettingsService;
use App\Support\Money;

/**
 * Order totals, with VAT. In the UAE, VAT applies to delivery and fees too,
 * so tax is worked out on the whole amount.
 *
 *  - Prices include VAT (default): total = items + delivery + fee; VAT is the part already inside.
 *  - Prices exclude VAT: VAT is added on top.
 */
class TotalsCalculator
{
    public function __construct(private SettingsService $settings) {}

    public function calculate(Money $subtotal, Money $shipping, Money $fee, ?Money $discount = null): OrderTotals
    {
        $discount ??= Money::zero($subtotal->currency);
        $enabled = $this->settings->bool('tax.enabled');
        $rate = $enabled ? (string) ($this->settings->get('tax.rate') ?: '0') : '0';
        $inclusive = $this->settings->bool('tax.prices_include_tax', true);
        // A discount lowers the amount VAT is due on.
        $taxable = $subtotal->subtract($discount)->add($shipping)->add($fee);

        if (! $enabled || (float) $rate <= 0) {
            $tax = Money::zero($subtotal->currency);
            $grand = $taxable;
        } elseif ($inclusive) {
            $tax = $taxable->includedTax($rate);
            $grand = $taxable;
        } else {
            $tax = $taxable->percentage($rate);
            $grand = $taxable->add($tax);
        }

        return new OrderTotals(
            $subtotal, $shipping, $fee, $tax, $grand,
            $inclusive,
            number_format((float) $rate, 2, '.', ''),
            $enabled ? $this->settings->trans('tax.label') : null,
            $discount,
        );
    }
}
