<?php

namespace App\Services\Payments;

class PaymentManager
{
    /** @return array<string, PaymentGateway> every gateway, keyed by code */
    public function all(): array
    {
        $gateways = [app(CashOnDeliveryGateway::class), app(StripeGateway::class)];

        return collect($gateways)->keyBy(fn (PaymentGateway $g) => $g->code())->all();
    }

    /** @return array<string, PaymentGateway> gateways the customer can choose now */
    public function available(): array
    {
        return array_filter($this->all(), fn (PaymentGateway $g) => $g->isAvailable());
    }

    public function get(string $code): ?PaymentGateway
    {
        return $this->all()[$code] ?? null;
    }
}
