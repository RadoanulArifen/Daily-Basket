<?php

namespace App\Themes\Furnivo;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Everything the Furnivo home page shows. Product rows follow the lists saved in
 * settings (furnivo.rails, from the design); rows with no list fall back to a rule.
 */
class HomeData
{
    public function __construct(private StorefrontCatalog $catalog) {}

    public function hero(): Collection
    {
        return Banner::query()->where('placement', 'hero')->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with('image')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function brands(): Collection
    {
        return Brand::query()->active()->whereNotNull('logo')->get();
    }

    /** @return Collection<int, array{category: Category, count: int}> */
    public function categories(): Collection
    {
        $categories = Category::query()->active()->whereNull('parent_id')->with('image')->orderBy('sort_order')->limit(5)->get();
        $counts = Cache::remember('furnivo.category_counts', 600, fn () => $categories->mapWithKeys(
            fn ($c) => [$c->id => $this->catalog->inCategory($this->catalog->products(), $c)->count()])->all());

        return $categories->map(fn ($c) => ['category' => $c, 'count' => $counts[$c->id] ?? 0]);
    }

    /** @return list<array{key: string, eyebrow: string, title: string, products: Collection, tinted: bool}> */
    public function rails(): array
    {
        $rails = (array) settings()->get('furnivo.rails', []);

        return collect($rails)->values()->map(function ($rail, $i) {
            $slugs = (array) ($rail['products'] ?? []);
            $query = $this->catalog->products()->with('brandRecord');
            $products = $slugs
                ? $query->whereIn('slug', $slugs)->get()->sortBy(fn ($p) => array_search($p->slug, $slugs, true))->values()
                : $this->fallback($rail['key'] ?? '', $query);

            return ['key' => $rail['key'] ?? 'rail'.$i, 'eyebrow' => $rail['eyebrow'] ?? '', 'title' => $rail['title'] ?? '', 'products' => $products, 'tinted' => $i % 2 === 1];
        })->filter(fn ($r) => $r['products']->isNotEmpty())->values()->all();
    }

    /** @return list<array{label: string, url: string, image: ?Media}> */
    public function explore(): array
    {
        $tiles = (array) settings()->get('furnivo.explore', []);
        $media = Media::query()->whereIn('id', array_filter(array_column($tiles, 'image_id')))->get()->keyBy('id');

        return array_map(fn ($t) => ['label' => $t['label'], 'url' => $t['url'], 'image' => $media->get($t['image_id'] ?? 0)], $tiles);
    }

    private function fallback(string $key, $query): Collection
    {
        return match ($key) {
            'new' => $query->latest('products.id')->limit(8)->get(),
            'top' => $query->orderByDesc('rating_average')->limit(8)->get(),
            'best', 'trending' => $query->orderByDesc('rating_count')->limit(8)->get(),
            default => $query->where('is_featured', true)->limit(8)->get(),
        };
    }
}
