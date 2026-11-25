<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FulfillmentItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['fulfillment_id', 'order_item_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
