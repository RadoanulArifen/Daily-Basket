<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Someone waiting for a piece to come back.
 *
 * Kept after the email is sent (with notified_at filled in) so the same person is not told
 * twice when stock goes up and down, and so you can see what people are waiting for.
 */
class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = ['product_variant_id', 'user_id', 'email', 'token', 'locale'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $alert) {
            $alert->token ??= Str::random(48);
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Still waiting to be told. */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }
}
