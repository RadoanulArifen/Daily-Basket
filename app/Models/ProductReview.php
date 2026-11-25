<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use Auditable;

    protected $fillable = ['product_id', 'user_id', 'name', 'rating', 'title', 'body', 'status', 'verified_purchase', 'locale'];

    protected $attributes = [
        'title' => null,
        'status' => 'pending',
        'verified_purchase' => false,
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'verified_purchase' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_product_review')->withPivot('position')->orderByPivot('position');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Keep the product's average and count in step with approved reviews. */
    public static function refreshProductRating(int $productId): void
    {
        $stats = static::query()->approved()->where('product_id', $productId)->selectRaw('count(*) as c, avg(rating) as a')->first();

        Product::withTrashed()->whereKey($productId)->update([
            'rating_count' => (int) $stats->c,
            'rating_average' => $stats->c ? round((float) $stats->a, 2) : null,
        ]);
    }
}
