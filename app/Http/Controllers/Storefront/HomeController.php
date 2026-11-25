<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(StorefrontCatalog $catalog): View
    {
        $featured = $catalog->products()->where('is_featured', true)->latest('published_at')->limit(8)->get();

        // Show whole rows of four on desktop (a lone product on a second row looks broken).
        if ($featured->count() > 4) {
            $featured = $featured->take(intdiv($featured->count(), 4) * 4);
        }

        // Product counts on the category tiles: cached briefly, they rarely change.
        $categoryCounts = \Illuminate\Support\Facades\Cache::remember('home.category_counts', 600, fn () => \App\Models\Category::query()
            ->active()->whereNull('parent_id')->get()
            ->mapWithKeys(fn ($c) => [$c->id => $catalog->inCategory($catalog->products(), $c)->count()])->all());

        return view('storefront.home.index', [
            'categoryCounts' => $categoryCounts,
            'featured' => $featured,
            'newest' => $catalog->products()->whereKeyNot($featured->pluck('id'))->latest('published_at')->limit(4)->get(),
            'categories' => Category::query()->active()->whereNull('parent_id')->with('image')->orderBy('sort_order')->limit(6)->get()
                ->each(function (Category $category) use ($catalog) {
                    // Categories without their own image borrow one from a product inside them.
                    if (! $category->image) {
                        $category->setRelation('coverProduct', $catalog->inCategory($catalog->products(), $category)->orderByDesc('is_featured')->first());
                    }
                }),
            'hasSale' => $catalog->onSale($catalog->products())->exists(),
            // Admin → Content → Banners. Without any, the built-in hero is shown.
            'heroBanner' => \App\Models\Banner::query()->live()->where('placement', 'hero')->with('image')->first(),
            // Recent 4–5 star reviews for the "what customers say" strip.
            'reviews' => \App\Models\ProductReview::query()->approved()->where('rating', '>=', 4)
                ->whereHas('product', fn ($q) => $q->where('status', 'active'))
                ->with('product:id,name,slug')->latest('id')->limit(3)->get(),
            'promoBanners' => \App\Models\Banner::query()->live()->where('placement', 'promo')->with('image')->limit(3)->get(),
        ]);
    }
}
