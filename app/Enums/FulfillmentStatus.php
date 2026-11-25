<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Partial = 'partial';          // some items shipped, the rest still to go
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public function label(): string
    {
        return __("admin.orders.fulfillment.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unfulfilled => 'warning',
            self::Partial => 'warning',
            self::Shipped => 'info',
            self::Delivered => 'success',
        };
    }
}
