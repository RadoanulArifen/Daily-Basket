<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['refund_id', 'order_item_id', 'quantity', 'restocked'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'restocked' => 'boolean'];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
