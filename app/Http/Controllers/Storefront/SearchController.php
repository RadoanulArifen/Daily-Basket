<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ListingRequest;
use App\Services\Catalog\StorefrontCatalog;
use App\Services\Catalog\ProductSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public const MIN_LENGTH = 2;

    public function __invoke(ListingRequest $request, StorefrontCatalog $catalog): View
    {
        $filters = $request->filters();
        $term = $filters['q'];
        $products = null;
        $facets = null;
        $brands = collect();

        if (mb_strlen($term) >= self::MIN_LENGTH) {
            $found = $catalog->search($catalog->products(), $term);
            // The same filters and counts as a category listing, over what the search found.
            $facets = app(\App\Services\Catalog\ProductFacets::class)->counts(clone $found);
            $brands = \App\Models\Brand::query()->whereIn('id', array_keys(array_filter($facets['brands'])))->get()
                ->sortBy(fn ($b) => array_search($b->id, $facets['brand_order'], true))->values();
            $products = $catalog->paginate($catalog->filter($found, $filters, 'featured'));
        }

                // Nothing found: worth knowing what the catalogue is missing, or what people call it.
        if (mb_strlen($term) >= self::MIN_LENGTH && $products !== null && $products->total() === 0) {
            app(\App\Services\Analytics\Funnel::class)->missedSearch($term);
        }

        return view('storefront.search.index', [
            'term' => $term,
            'products' => $products,
            'filters' => $filters,
            'sort' => $filters['sort'] ?? 'featured',
            'facets' => $facets,
            'brands' => $brands,
        ]);
    }

    /**
     * Live results while the visitor types, for the header search box.
     *
     * Deliberately small: a handful of pieces, the price and a picture — enough to recognise
     * something, not a second listing page. When nothing matches, the nearest catalogue word
     * comes back so the box can offer it.
     */
    public function suggest(Request $request, StorefrontCatalog $catalog, ProductSearch $search): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return response()->json(['results' => [], 'term' => $term]);
        }

        $products = $catalog->search($catalog->products(), $term)
            ->with(['media', 'variants'])
            ->limit(6)
            ->get();

        return response()->json([
            'term' => $term,
            'results' => $products->map(fn ($product) => [
                'name' => $product->name,
                'url' => store_route('products.show', ['slug' => $product->slug]),
                'price' => ($variant = $product->variants->sortBy(fn ($v) => $v->price->minor)->first())
                    ? price($variant->price)
                    : null,
                'image' => $product->media->first()?->url('thumb'),
            ])->values()->all(),
            // Only worth offering when nothing was found.
            'suggestion' => $products->isEmpty() ? $search->suggestion($term) : null,
            'all_url' => store_route('search', ['q' => $term]),
        ]);
    }
}
