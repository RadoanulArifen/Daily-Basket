<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';     // COD not yet collected, or card payment not finished
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Voided = 'voided';       // order cancelled before any money was taken

    public function label(): string
    {
        return __("admin.orders.payment.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::PartiallyRefunded => 'warning',
            self::Refunded, self::Voided => 'neutral',
        };
    }
}
