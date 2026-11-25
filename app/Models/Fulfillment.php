<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One shipment of some or all of an order's items. */
class Fulfillment extends Model
{
    protected $fillable = ['order_id', 'sequence', 'carrier', 'tracking_number', 'label_url', 'delivered_at', 'admin_id'];

    protected $attributes = ['carrier' => null, 'tracking_number' => null, 'delivered_at' => null, 'admin_id' => null];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'sequence' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FulfillmentItem::class);
    }

    public function label(): string
    {
        return $this->order->number.'-F'.$this->sequence;
    }
}
