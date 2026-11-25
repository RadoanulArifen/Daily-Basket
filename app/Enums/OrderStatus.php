<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Completed = 'completed';   // paid and delivered
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("admin.orders.status.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
