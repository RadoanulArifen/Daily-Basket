<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    use Auditable;

    public const STATUSES = ['requested', 'approved', 'rejected', 'received', 'completed'];

    public const REASONS = ['changed_mind', 'damaged', 'wrong_item', 'not_as_described', 'size_colour', 'other'];

    protected $fillable = ['order_id', 'status', 'resolution', 'reason', 'customer_note', 'admin_note', 'restocked'];

    protected $attributes = [
        'status' => 'requested',
        'customer_note' => null,
        'admin_note' => null,
        'restocked' => false,
        'number' => null,
    ];

    protected function casts(): array
    {
        return ['restocked' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['requested', 'approved', 'received'], true);
    }

    public function tone(): string
    {
        return match ($this->status) {
            'requested' => 'warning', 'approved', 'received' => 'info', 'completed' => 'success', default => 'neutral',
        };
    }
}
