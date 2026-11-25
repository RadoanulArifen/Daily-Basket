<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Money given back on an order, with the items it covers. */
class Refund extends Model
{
    protected $fillable = ['order_id', 'amount', 'shipping_amount', 'reason', 'gateway_reference', 'admin_id'];

    protected $attributes = ['shipping_amount' => '0.00', 'reason' => null, 'gateway_reference' => null, 'admin_id' => null];

    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'shipping_amount' => MoneyCast::class];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
