<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use Auditable;

    public const TYPES = ['percentage', 'fixed', 'free_shipping'];

    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'usage_limit', 'usage_limit_per_customer', 'starts_at', 'ends_at', 'is_active'];

    protected $attributes = [
        'value' => '0.00',
        'min_subtotal' => null,
        'usage_limit' => null,
        'usage_limit_per_customer' => null,
        'used_count' => 0,
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'min_subtotal' => MoneyCast::class,
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = mb_strtoupper(trim($value));
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    /** "15% off", "AED 50 off", "Free delivery" */
    public function summary(): string
    {
        return match ($this->type) {
            'percentage' => __('admin.discounts.summary.percentage', ['value' => rtrim(rtrim((string) $this->value, '0'), '.')]),
            'fixed' => __('admin.discounts.summary.fixed', ['value' => price_admin(\App\Support\Money::of((string) $this->value, config('store.base_currency')))]),
            default => __('admin.discounts.summary.free_shipping'),
        };
    }

    /** live | scheduled | expired | used_up | disabled */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'disabled',
            $this->starts_at && $this->starts_at->isFuture() => 'scheduled',
            $this->ends_at && $this->ends_at->isPast() => 'expired',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'used_up',
            default => 'live',
        };
    }
}
