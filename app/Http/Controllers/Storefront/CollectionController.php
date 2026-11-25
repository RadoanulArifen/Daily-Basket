<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ListingRequest;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\View\View;

/**
 * Product listings: /shop, /collections/{category}, and the built-in
 * /collections/new-in and /collections/sale.
 */
class CollectionController extends Controller
{
    public function all(ListingRequest $request, StorefrontCatalog $catalog): View
    {
        return $this->listing($request, $catalog, [
            'title' => __('store.collections.all'),
            'description' => __('store.collections.all_text'),
            'query' => $catalog->products(),
            'defaultSort' => 'featured',
        ]);
    }

    public function show(ListingRequest $request, StorefrontCatalog $catalog, string $slug): View
    {
        $query = $catalog->products();

        $page = match ($slug) {
            'new-in' => [
                'title' => __('store.collections.new_in'),
                'description' => __('store.collections.new_in_text'),
                'query' => $catalog->newIn($query),
                'defaultSort' => 'newest',
            ],
            'sale' => [
                'title' => __('store.collections.sale'),
                'description' => __('store.collections.sale_text'),
                'query' => $catalog->onSale($query),
                'defaultSort' => 'featured',
            ],
            default => null,
        };

        if (! $page) {
            $category = $catalog->findCategory($slug) ?? abort(404);

            $page = [
                'title' => $category->name,
                'description' => $category->description,
                'metaTitle' => $category->seo_title ?: $category->name,
                'metaDescription' => $category->seo_description ?: $category->description,
                'category' => $category,
                'subcategories' => $catalog->subcategories($category),
                'query' => $catalog->inCategory($query, $category),
                'defaultSort' => 'featured',
            ];
        }

        return $this->listing($request, $catalog, $page);
    }

    private function listing(ListingRequest $request, StorefrontCatalog $catalog, array $page): View
    {
        $filters = $request->filters();
        // The FreshNest listing shows departments and stock/offer filters without facet counts.
        if (config('store.theme') !== 'freshnest') {
            $facets = app(\App\Services\Catalog\ProductFacets::class)->counts(clone $page['query']);
            $page['facets'] = $facets;
            $page['brands'] = \App\Models\Brand::query()
                ->whereIn('id', array_keys(array_filter($facets['brands'])))->get()
                ->sortBy(fn ($brand) => array_search($brand->id, $facets['brand_order'], true))->values();
        }

        $products = $catalog->paginate($catalog->filter($page['query'], $filters, $page['defaultSort']));

        return view('storefront.collections.show', $page + [
            'products' => $products,
            'filters' => $filters,
            'sort' => $filters['sort'] ?? $page['defaultSort'],
            'category' => null,
            'subcategories' => collect(),
        ]);
    }
}
