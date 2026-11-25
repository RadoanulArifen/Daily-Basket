<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\RecentlyViewed;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Request $request, StorefrontCatalog $catalog, string $slug): View
    {
        $product = $catalog->findProduct($slug) ?? abort(404);

        // What the visitor looked at before this, and remember this one for next time.
        $seen = app(RecentlyViewed::class);
        $recentlyViewed = $seen->products(except: $product);
        $seen->record($product);
        app(\App\Services\Analytics\Funnel::class)->record('viewed');
        $variants = $product->variants;
        // Each variant belongs to this product; saying so avoids a query per variant.
        $variants->each->setRelation('product', $product);

        // ?variant=12 opens a specific variant (shared links, cart links).
        $selected = $variants->firstWhere('id', (int) $request->query('variant'))
            ?? $variants->first(fn (ProductVariant $v) => $v->isInStock())
            ?? $variants->first();

        // "Recently viewed products": the last 12, newest first, kept for this visit.
        $recent = array_values(array_unique(array_merge([$product->id], (array) session('recently_viewed', []))));
        session(['recently_viewed' => array_slice($recent, 0, 12)]);

        return view('storefront.products.show', [
            'recentlyViewed' => $recentlyViewed,
            'product' => $product,
            'selected' => $selected,
            'config' => $this->config($product, $selected),
            'related' => $catalog->related($product),
            'category' => $product->categories->first(),
        ]);
    }

    /** Data for the option picker, prices already converted to the shopper's currency. */
    private function config(Product $product, ProductVariant $selected): array
    {
        $imageIndex = $product->media->pluck('id')->flip();

        return [
            'selected' => $selected->id,
            'options' => $product->options->map(fn ($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'values' => $o->values->map(fn ($v) => ['id' => $v->id, 'label' => $v->value])->values(),
            ])->values(),
            'variants' => $product->variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'values' => $v->optionValues->pluck('id')->values(),
                'price' => price($v->price),
                'compare' => $v->isOnSale() ? price($v->compare_at_price) : null,
                'save' => $v->isOnSale() ? (int) round(100 - ($v->price->minor / $v->compare_at_price->minor) * 100) : null,
                'available' => $v->isInStock(),
                'left' => $v->track_inventory && ! $v->allow_backorder && $v->stock_quantity <= 5 ? max(0, $v->stock_quantity) : null,
                'image' => $v->media_id !== null ? $imageIndex->get($v->media_id) : null,
                'sku' => $v->sku,
            ])->values(),
        ];
    }
}
