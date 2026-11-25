<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Payment taken outside the website (in the shop, bank transfer, payment link) for orders
 * an admin creates. Never offered to shoppers at checkout.
 */
class ManualGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return __('admin.orders.methods.manual');
    }

    public function description(): ?string
    {
        return null;
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function fee(): string
    {
        return '0.00';
    }

    public function start(Order $order): ?string
    {
        return null;
    }
}
