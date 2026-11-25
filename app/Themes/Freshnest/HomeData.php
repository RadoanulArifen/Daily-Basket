<?php

namespace App\Themes\Freshnest;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Catalog\StorefrontCatalog;
use App\Services\Wishlist\WishlistService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Everything the FreshNest home page shows, from the store's own data:
 * banners (Content → Banners, by "Where"), categories, products, deals (Marketing →
 * Deals) and discount codes (Marketing → Discounts). Cached per request only.
 */
class HomeData
{
    private ?Collection $banners = null;

    private ?Collection $categoryRows = null;

    private ?Collection $products = null;

    private ?Collection $promotions = null;

    public function __construct(private StorefrontCatalog $catalog, private WishlistService $wishlist) {}

    /** Live banners for one place, in their order. */
    public function banners(string $placement): Collection
    {
        $this->banners ??= Banner::query()->live()->with('image')->get()->groupBy('placement');

        return $this->banners->get($placement, collect())->values();
    }

    public function banner(string $placement): ?Banner
    {
        return $this->banners($placement)->first();
    }

    /** @return Collection<int, array{category: Category, count: int}> top-level categories with product counts */
    public function categories(): Collection
    {
        if ($this->categoryRows !== null) {
            return $this->categoryRows;
        }

        $categories = Category::query()->active()->whereNull('parent_id')->with('image')->orderBy('sort_order')->get();
        $counts = Cache::remember('freshnest.category_counts', 600, fn () => $categories->mapWithKeys(
            fn ($c) => [$c->id => $this->catalog->inCategory($this->catalog->products(), $c)->count()])->all());

        return $this->categoryRows = $categories->map(fn ($c) => ['category' => $c, 'count' => $counts[$c->id] ?? 0]);
    }

    /** All live products, loaded once. */
    public function products(): Collection
    {
        return $this->products ??= $this->catalog->products()->with(['categories', 'brandRecord', 'variants.optionValues'])->orderBy('products.id')->get();
    }

    /** Categories that have products, for the Fresh Picks tabs: [name => slug]. */
    public function tabs(): Collection
    {
        return $this->categories()->filter(fn ($c) => $c['count'] > 0)->take(4)
            ->map(fn ($c) => ['name' => \Illuminate\Support\Str::before($c['category']->name, ' &'), 'slug' => $c['category']->slug, 'count' => $c['count']])->values();
    }

    public function inCategory(string $slug, int $limit): Collection
    {
        return $this->products()->filter(fn ($p) => $p->categories->contains('slug', $slug))->take($limit)->values();
    }

    public function topRated(int $limit = 3): Collection
    {
        return $this->products()->sortByDesc(fn ($p) => [(float) $p->rating_average, $p->rating_count])->take($limit)->values();
    }

    public function bestSellers(int $limit = 3): Collection
    {
        return $this->products()->sortByDesc('rating_count')->take($limit)->values();
    }

    public function newest(int $limit = 3): Collection
    {
        return $this->products()->sortByDesc('id')->take($limit)->values();
    }

    /** The biggest saving in each category (the design's "Daily essentials"). */
    public function essentials(int $limit = 4, ?Product $except = null): Collection
    {
        return $this->products()->reject(fn ($p) => $except && $p->id === $except->id)
            ->groupBy(fn ($p) => $p->categories->first()?->id)
            ->map(fn ($group) => $group->sortByDesc(fn ($p) => $this->card($p)['pct'] ?? 0)->first())
            ->filter()->take($limit)->values();
    }

    /** Deal of the Day: the product the "deal" banner links to, else the biggest saving. */
    public function dealProduct(): ?Product
    {
        $url = (string) $this->banner('deal')?->url;
        if (preg_match('#/products/([a-z0-9-]+)#', $url, $m) && ($p = $this->products()->firstWhere('slug', $m[1]))) {
            return $p;
        }

        return $this->products()->sortByDesc(fn ($p) => $this->card($p)['pct'] ?? 0)->first();
    }

    /** Live buy-X-get-Y deals. */
    public function promotions(): Collection
    {
        return $this->promotions ??= Promotion::query()->live()->with('category')->get();
    }

    /** The first deal that applies to a product, as its label ("Buy 1 Get 1"). */
    public function offerFor(Product $product): ?Promotion
    {
        return $this->promotions()->first(fn (Promotion $promo) => match ($promo->applies_to) {
            'products' => in_array($product->id, array_map('intval', (array) $promo->product_ids), true),
            'category' => $product->categories->contains('id', $promo->category_id),
            default => true,
        });
    }

    /** A product-specific deal to feature ("Buy 1 Get 1" card), by how many it takes. */
    public function featuredDeal(bool $bundle): ?array
    {
        $promo = $this->promotions()->filter(fn ($p) => $p->applies_to === 'products' && ($bundle ? $p->buy_quantity + $p->get_quantity > 2 : $p->buy_quantity + $p->get_quantity <= 2))->first();
        $product = $promo ? $this->products()->firstWhere('id', (int) ((array) $promo->product_ids)[0]) : null;
        if (! $promo || ! $product) {
            return null;
        }
        $card = $this->card($product);
        $qty = $promo->buy_quantity + $promo->get_quantity;
        $pay = $card['price']->multiply($promo->buy_quantity)->add($card['price']->percentage(100 - $promo->get_percent)->multiply($promo->get_quantity));

        return ['promotion' => $promo, 'card' => $card, 'qty' => $qty, 'total' => $pay];
    }

    /** A spend-and-save discount code ("Spend $40, get $5 off") for the middle offer card. */
    public function spendDiscount(): ?Discount
    {
        return Discount::query()->where('is_active', true)->where('type', 'fixed')->whereNotNull('min_subtotal')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('min_subtotal')->first();
    }

    /**
     * What a product card needs: cheapest variant's price and pack size, saving, rating,
     * photo, wishlist state and any deal.
     *
     * @return array{product: Product, url: string, name: string, image: ?\App\Models\Media, weight: ?string, variant_id: int, price: Money, old: ?Money, pct: ?int, rating: string, reviews: int, wished: bool, offer: ?string, category: ?string, stock: ?int}
     */
    public function card(Product $product): array
    {
        $product->loadMissing(['variants.optionValues', 'categories', 'media']);
        $variant = $product->variants->where('is_active', true)->sortBy(fn ($v) => $v->price->minor)->first();
        $old = $variant->compare_at_price && $variant->compare_at_price->isGreaterThan($variant->price) ? $variant->compare_at_price : null;

        return [
            'product' => $product,
            'url' => store_route('products.show', ['slug' => $product->slug]),
            'name' => $product->name,
            'image' => $product->media->first(),
            'weight' => $variant->title() ?: null,
            'variant_id' => $variant->id,
            'price' => $variant->price,
            'old' => $old,
            'pct' => $old ? (int) round((1 - $variant->price->minor / $old->minor) * 100) : null,
            'rating' => number_format((float) $product->rating_average, 1),
            'reviews' => (int) $product->rating_count,
            'wished' => $this->wishlist->has($product->id),
            'offer' => $this->offerFor($product)?->label(),
            'category' => $product->categories->first()?->name,
            'category_slug' => $product->categories->first()?->slug,
            'stock' => $variant->track_inventory ? (int) $variant->stock_quantity : null,
        ];
    }

    /** "4.8" and "5,121": the store's average rating and how many ratings it rests on. */
    public function storeRating(): array
    {
        $products = $this->products()->filter(fn ($p) => $p->rating_count > 0);
        $count = $products->sum('rating_count');
        $avg = $count ? $products->sum(fn ($p) => (float) $p->rating_average * $p->rating_count) / $count : 0;

        return ['average' => number_format($avg, 1), 'count' => $count];
    }
}
