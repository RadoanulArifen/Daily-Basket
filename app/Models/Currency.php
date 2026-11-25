<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Currency extends Model
{
    protected static function booted(): void
    {
        // Every price in the shop is stored in the base currency, so the base can never be
        // switched off — a shop with no currency to show prices in has no shop.
        static::saving(function (self $currency) {
            if ($currency->is_base) {
                $currency->is_active = true;
            }
        });
    }

    use Auditable;

    /** @use HasFactory<CurrencyFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $attributes = [
        'symbol_ar' => null,
        'decimal_places' => 2,
        'exchange_rate' => '1',
        'is_base' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_ar',
        'decimal_places',
        'exchange_rate',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'exchange_rate' => 'decimal:8',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_base')->orderBy('sort_order')->orderBy('code');
    }

    public function symbolFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'ar' && filled($this->symbol_ar) ? $this->symbol_ar : $this->symbol;
    }
}
