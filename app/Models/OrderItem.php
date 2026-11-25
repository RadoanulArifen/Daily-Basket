<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class OrderItem extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'variant_title'];

    protected $guarded = ['id'];

    protected $attributes = [
        'product_id' => null,
        'product_variant_id' => null,
        'variant_title' => null,
        'sku' => null,
        'image' => null,
        'compare_at_price' => null,
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 
            'unit_price' => MoneyCast::class,
            'compare_at_price' => MoneyCast::class,
            'line_total' => MoneyCast::class,
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    /** "Frame: Oak · Upholstery: Bouclé" */
    public function optionsLabel(): string
    {
        return collect((array) $this->options)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ');
    }
}
