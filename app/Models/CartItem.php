<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity', 'options', 'options_hash'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'options' => 'array'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        // Include soft-deleted variants so the cart can tell the customer the item is gone.
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    /** Keep only short text choices, in a stable order. */
    public static function normaliseOptions(array $options): array
    {
        $clean = [];
        foreach (array_slice($options, 0, 12, true) as $label => $value) {
            $label = trim(mb_substr((string) $label, 0, 40));
            $value = trim(mb_substr((string) $value, 0, 60));
            if ($label !== '' && $value !== '') {
                $clean[$label] = $value;
            }
        }

        return $clean;
    }

    public static function hashOptions(array $options): string
    {
        return $options ? sha1(json_encode($options, JSON_UNESCAPED_UNICODE)) : '';
    }

    /** "Frame: Oak · Upholstery: Bouclé" */
    public function optionsLabel(): string
    {
        return collect((array) $this->options)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ');
    }
}
