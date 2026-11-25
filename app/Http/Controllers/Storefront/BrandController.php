<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductReview;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function show(string $slug, Request $request, StorefrontCatalog $catalog): View
    {
        $brand = Brand::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $all = $catalog->products()->where('brand_id', $brand->id);
        $productIds = (clone $all)->pluck('products.id');

        // The types this brand makes, with a count each, for the tabs.
        $tabs = Category::query()->whereNull('parent_id')->active()->orderBy('sort_order')->get()
            ->map(fn ($category) => [
                'category' => $category,
                'count' => $catalog->inCategory((clone $all), $category)->count(),
            ])->filter(fn ($tab) => $tab['count'] > 0)->values();

        $chosen = $tabs->firstWhere(fn ($tab) => $tab['category']->slug === $request->query('type'))['category'] ?? null;
        $listing = $chosen ? $catalog->inCategory((clone $all), $chosen) : (clone $all);

        // The brand's standing comes from reviews of its own pieces.
        $reviews = ProductReview::query()->approved()->whereIn('product_id', $productIds);
        $sort = $request->query('reviews') === 'rating' ? 'rating' : 'recent';

        return view('storefront.brands.show', [
            'brand' => $brand,
            'products' => $listing->with(['brandRecord', 'media', 'variants'])->paginate(8)->withQueryString(),   // 8 to a page, as in the design
            'totalPieces' => $productIds->count(),
            'tabs' => $tabs,
            'chosen' => $chosen,
            'madeToOrder' => (clone $all)->where('made_to_order', true)->exists(),
            'inStock' => (clone $all)->where('made_to_order', false)->exists(),
            'reviewCount' => (clone $reviews)->count(),
            'reviewAverage' => round((clone $reviews)->avg('rating') ?? 0, 1),
            'reviews' => (clone $reviews)->when($sort === 'rating', fn ($q) => $q->orderByDesc('rating')->orderByDesc('id'), fn ($q) => $q->latest('id'))->limit(3)->get(),   // the design shows three
            'reviewSort' => $sort,
        ]);
    }
}
