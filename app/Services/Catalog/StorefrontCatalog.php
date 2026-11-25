<?php

namespace App\Services\Catalog;

use App\Support\Search;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Currency\CurrencyService;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything the storefront is allowed to show: only Active products that have
 * at least one active variant, in active categories.
 */
class StorefrontCatalog
{
    public const SORTS = ['featured', 'newest', 'price_asc', 'price_desc', 'name', 'rating'];

    public const PER_PAGE = 9;   // the design shows a 3 × 3 grid per page

    /** How recently a product must have gone live to count as "New in". */
    public const NEW_IN_DAYS = 60;

    public function __construct(private CurrencyService $currencies) {}

    public function products(): Builder
    {
        return Product::query()
            ->active()
            ->whereHas('variants', fn (Builder $v) => $v->where('is_active', true))
            ->with([
                'media',
                'variants' => fn ($v) => $v->where('is_active', true),
            ]);
    }

    public function findProduct(string $slug): ?Product
    {
        return $this->products()
            ->where('slug', $slug)
            ->with([
                'options.values',
                'variants.optionValues',
                'categories' => fn ($c) => $c->where('is_active', true),
            ])
            ->first();
    }

    public function findCategory(string $slug): ?Category
    {
        return Category::query()->active()->where('slug', $slug)->with(['parent', 'image'])->first();
    }

    /** Active sub-categories, for the chips at the top of a category page. */
    public function subcategories(Category $category): Collection
    {
        return Category::query()->active()->where('parent_id', $category->id)->orderBy('sort_order')->get();
    }

    public function inCategory(Builder $query, Category $category): Builder
    {
        $ids = Category::query()->active()->whereIn('id', $category->descendantIds())->pluck('id');

        return $query->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids));
    }

    public function newIn(Builder $query): Builder
    {
        $recent = (clone $query)->where('published_at', '>=', now()->subDays(self::NEW_IN_DAYS));

        // A quiet store still gets a useful "New in" page: fall back to the newest products.
        return $recent->exists()
            ? $recent
            : $query->whereIn('products.id', (clone $query)->reorder()->latest('published_at')->limit(self::PER_PAGE)->pluck('products.id'));
    }

    public function onSale(Builder $query): Builder
    {
        return $query->whereHas('variants', fn (Builder $v) => $v
            ->where('is_active', true)
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price'));
    }

    /** Catalogue search: every word must match, synonyms included (see ProductSearch). */
    public function search(Builder $query, string $term): Builder
    {
        return app(ProductSearch::class)->apply($query, $term);
    }

    /**
     * Apply the shopper's filters (price in their currency, in stock, on sale) and sort order.
     *
     * @param  array{sort?: string, min?: string|null, max?: string|null, in_stock?: bool, sale?: bool}  $filters
     */
    public function filter(Builder $query, array $filters, string $defaultSort = 'featured'): Builder
    {
        $minPrice = ProductVariant::query()
            ->selectRaw('min(price)')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_active', true);

        $query->addSelect(['products.*', 'min_price' => $minPrice]);

        // A chosen price band simply sets the price range (before it is applied below).
        if (($band = $filters['band'] ?? null) && $band !== 'all') {
            $filters['min'] = ProductFacets::PRICE_BANDS[$band]['min'] === null ? ($filters['min'] ?? null) : (string) ProductFacets::PRICE_BANDS[$band]['min'];
            $filters['max'] = ProductFacets::PRICE_BANDS[$band]['max'] === null ? ($filters['max'] ?? null) : (string) ProductFacets::PRICE_BANDS[$band]['max'];
        }

        // Brand, materials, colour and made-to-order.
        app(ProductFacets::class)->apply($query, $filters);
        $filters['in_stock'] = ! empty($filters['in_stock']) || ($filters['availability'] ?? null) === 'in_stock';

        foreach (['min' => '>=', 'max' => '<='] as $key => $operator) {
            if (($filters[$key] ?? null) !== null && $filters[$key] !== '') {
                $base = $this->toBase((string) $filters[$key]);
                $query->whereHas('variants', fn (Builder $v) => $v->where('is_active', true)->where('price', $operator, $base));
            }
        }

        if (! empty($filters['in_stock'])) {
            $query->whereHas('variants', fn (Builder $v) => $v->where('is_active', true)->where(fn ($s) => $s
                ->where('track_inventory', false)
                ->orWhere('allow_backorder', true)
                ->orWhere('stock_quantity', '>', 0)));
        }

        if (! empty($filters['sale'])) {
            $this->onSale($query);
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTS, true) ? $filters['sort'] : $defaultSort;

        match ($sort) {
            'newest' => $query->orderByDesc('published_at'),
            'price_asc' => $query->orderBy('min_price'),
            'price_desc' => $query->orderByDesc('min_price'),
            'name' => $query->orderBy('name->'.app()->getLocale()),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };

        return $query->orderBy('products.id');
    }

    public function paginate(Builder $query): LengthAwarePaginator
    {
        return $query->paginate(\App\Services\Theme\ThemeSettings::PER_PAGE[config('store.theme')] ?? self::PER_PAGE)->withQueryString();
    }

    /** Other products from the same categories. */
    public function related(Product $product, int $limit = 4): Collection
    {
        $categoryIds = $product->categories->pluck('id');

        return $this->products()
            ->whereKeyNot($product->id)
            ->when($categoryIds->isNotEmpty(), fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /** A price typed in the shopper's currency, as a base-currency decimal string. */
    private function toBase(string $amount): string
    {
        $currency = $this->currencies->current();
        $rate = (float) $currency->exchange_rate ?: 1.0;

        // Filtering only needs to be approximately right; stored prices stay exact.
        return number_format(max(0, (float) $amount) / $rate, 2, '.', '');
    }
}
