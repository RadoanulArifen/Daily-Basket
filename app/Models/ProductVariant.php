<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price', 'compare_at_price', 'cost_price', 'weight_grams',
        'track_inventory', 'allow_backorder', 'media_id', 'is_active', 'position',
    ];

    // stock_quantity is deliberately not fillable: change it only through InventoryService.

    protected $attributes = [
        'sku' => null,
        'barcode' => null,
        'compare_at_price' => null,
        'cost_price' => null,
        'weight_grams' => null,
        'track_inventory' => true,
        'stock_quantity' => 0,
        'allow_backorder' => false,
        'media_id' => null,
        'is_active' => true,
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'compare_at_price' => MoneyCast::class,
            'cost_price' => MoneyCast::class,
            'weight_grams' => 'integer',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'integer',
            'allow_backorder' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_variant_option_value')
            ->join('product_options', 'product_options.id', '=', 'product_option_values.product_option_id')
            ->orderBy('product_options.position')
            ->select('product_option_values.*');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }

    /** "Large / Sand" — empty for a product without options. Needs ->optionValues loaded. */
    public function title(?string $locale = null): string
    {
        return $this->optionValues
            ->map(fn (ProductOptionValue $v) => $v->getTranslation('value', $locale ?? app()->getLocale()))
            ->implode(' / ');
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price->isGreaterThan($this->price);
    }

    /** How many can be bought right now; null means no limit (stock not tracked, or backorders allowed). */
    public function availableQuantity(): ?int
    {
        if (! $this->track_inventory || $this->allow_backorder) {
            return null;
        }

        return max(0, $this->stock_quantity);
    }

    /** Active, not deleted, and its product is live in the store. */
    public function isPurchasable(): bool
    {
        // Variants read from $product->variants have no product of their own loaded, and
        // lazy loading is off, so ask for it explicitly when it isn't there.
        $this->loadMissing('product');

        return $this->is_active
            && ! $this->trashed()
            && $this->product !== null
            && ! $this->product->trashed()
            && $this->product->status === \App\Enums\ProductStatus::Active;
    }

    public function isInStock(): bool
    {
        return ! $this->track_inventory || $this->allow_backorder || $this->stock_quantity > 0;
    }

    /** Active and sellable: stock not tracked, stock above zero, or back-orders allowed. */
    public function scopeInStock($query)
    {
        return $query->where('is_active', true)->where(fn ($s) => $s->where('track_inventory', false)
            ->orWhere('stock_quantity', '>', 0)->orWhere('allow_backorder', true));
    }

    /** Active, tracked, and between 1 and the low-stock level. */
    public function scopeLowStock($query, int $threshold)
    {
        return $query->where('is_active', true)->where('track_inventory', true)->whereBetween('stock_quantity', [1, $threshold]);
    }
}
