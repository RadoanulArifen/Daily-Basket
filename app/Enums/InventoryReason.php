<?php

namespace App\Enums;

enum InventoryReason: string
{
    case Initial = 'initial';       // stock entered when the variant was created
    case Received = 'received';     // new stock arrived
    case Correction = 'correction'; // stock count fixed
    case Damaged = 'damaged';       // broken, lost or unsellable
    case Returned = 'returned';     // customer return put back into stock
    case Edited = 'edited';         // changed from the product form
    case Sold = 'sold';             // an order was placed
    case OrderCancelled = 'order_cancelled'; // an order was cancelled and its items put back

    public function label(): string
    {
        return __("admin.inventory.reasons.{$this->value}");
    }

    /** Reasons the store owner can pick when adjusting stock by hand. */
    public static function manual(): array
    {
        return [self::Received, self::Correction, self::Damaged, self::Returned];
    }
}
