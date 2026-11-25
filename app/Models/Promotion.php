<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/** An automatic deal such as "Buy 2, get 1 free" (no code needed). */
class Promotion extends Model
{
    use Auditable, HasTranslations;

    public const SCOPES = ['all', 'category', 'products'];

    public array $translatable = ['name'];

    protected $fillable = ['name', 'buy_quantity', 'get_quantity', 'get_percent', 'applies_to', 'category_id', 'product_ids', 'starts_at', 'ends_at', 'is_active'];

    protected $attributes = [
        'name' => null,
        'get_percent' => 100,
        'applies_to' => 'all',
        'category_id' => null,
        'product_ids' => null,
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'buy_quantity' => 'integer', 'get_quantity' => 'integer', 'get_percent' => 'integer',
            'product_ids' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('id');
    }

    public function isLive(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /** "Buy 2, get 1 free" / "Buy 3, get 1 at 50% off" — used when no custom name is given. */
    public function summary(?string $locale = null): string
    {
        return $this->get_percent >= 100
            ? __('store.deals.summary_free', ['buy' => $this->buy_quantity, 'get' => $this->get_quantity], $locale)
            : __('store.deals.summary_percent', ['buy' => $this->buy_quantity, 'get' => $this->get_quantity, 'percent' => $this->get_percent], $locale);
    }

    public function label(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale ?? app()->getLocale(), false) ?: $this->summary($locale);
    }
}
