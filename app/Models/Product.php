<?php

namespace App\Models;

use App\Models\Concerns\HasRichText;
use App\Models\Concerns\Auditable;
use App\Enums\ProductStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use Auditable, HasRichText, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'short_description', 'description', 'dimensions', 'delivery_returns', 'trust_badges', 'seo_title', 'seo_description'];

    /** Written in the admin's HTML editor; cleaned on save (HasRichText). */
    protected array $richText = ['short_description', 'description', 'dimensions', 'delivery_returns'];

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'dimensions', 'delivery_returns', 'trust_badges', 'status', 'is_featured', 'made_to_order', 'from_price', 'configuration', 'brand_id',
        'brand', 'seo_title', 'seo_description', 'published_at',
    ];

    protected $attributes = [
        'short_description' => null,
        'description' => null,
        'dimensions' => null,
        'delivery_returns' => null,
        'trust_badges' => null,
        'status' => 'draft',
        'is_featured' => false,
        'made_to_order' => false,
        'from_price' => false,
        'configuration' => null,
        'brand_id' => null,
        'brand' => null,
        'seo_title' => null,
        'seo_description' => null,
        'published_at' => null,
        'rating_average' => null,
        'rating_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'made_to_order' => 'boolean',
            'from_price' => 'boolean',
            'configuration' => 'array',
            'published_at' => 'datetime',
            'rating_count' => 'integer',
            'rating_average' => 'decimal:2',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('position');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class)->withPivot('position')->orderByPivot('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    public function hasOptions(): bool
    {
        return $this->options->isNotEmpty();
    }

    /** First image, or null. Needs ->media loaded. */
    public function featuredImage(): ?Media
    {
        return $this->media->first();
    }

    /** [lowest, highest] price across active variants. Needs ->variants loaded. */
    public function priceRange(): array
    {
        $prices = $this->variants->where('is_active', true)->map(fn (ProductVariant $v) => $v->price->minor);

        if ($prices->isEmpty()) {
            return [null, null];
        }

        $currency = config('store.base_currency');

        return [Money::ofMinor($prices->min(), $currency), Money::ofMinor($prices->max(), $currency)];
    }

    /** Units in stock across variants that track stock, or null if none track it. */
    public function totalStock(): ?int
    {
        $tracked = $this->variants->where('track_inventory', true);

        return $tracked->isEmpty() ? null : (int) $tracked->sum('stock_quantity');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductReview::class)->latest('id');
    }

    public function brandRecord(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
