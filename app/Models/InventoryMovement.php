<?php

namespace App\Models;

use App\Enums\InventoryReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['product_variant_id', 'change', 'quantity_after', 'reason', 'note', 'admin_id'];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'quantity_after' => 'integer',
            'reason' => InventoryReason::class,
            'created_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
