<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * A way to pay. Add one by implementing this and listing it in PaymentManager.
 */
interface PaymentGateway
{
    public function code(): string;

    /** Shown at checkout. */
    public function label(): string;

    public function description(): ?string;

    public function isAvailable(): bool;

    /** Extra charge for using this method (e.g. cash on delivery fee), base currency decimal. */
    public function fee(): string;

    /**
     * Called right after the order is saved. Return a URL to send the customer to
     * (e.g. Stripe's payment page), or null when nothing more is needed now.
     */
    public function start(Order $order): ?string;
}
