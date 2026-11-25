<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Settings\SettingsService;

class CashOnDeliveryGateway implements PaymentGateway
{
    public function __construct(private SettingsService $settings) {}

    public function code(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return __('store.checkout.cod');
    }

    public function description(): ?string
    {
        return __('store.checkout.cod_text');
    }

    public function isAvailable(): bool
    {
        return $this->settings->bool('payments.cod_enabled', true);
    }

    public function fee(): string
    {
        return (string) ($this->settings->get('payments.cod_fee') ?: '0.00');
    }

    public function start(Order $order): ?string
    {
        // Paid to the courier on delivery; the store marks it paid in the admin.
        return null;
    }
}
