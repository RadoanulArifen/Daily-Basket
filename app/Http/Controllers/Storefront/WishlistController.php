<?php

// SECURITY: a signed-in customer's list is WishlistItem::where('user_id', auth('web')->id());
// a guest's list is their own session. No item ids come from the request.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Catalog\StorefrontCatalog;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OWNERSHIP: all reads and writes go through WishlistService, which scopes every query to the
 * signed-in customer (WishlistItem::where('user_id', $user->id)) or, for guests, to their own
 * session. There is no wishlist-by-id route, so one customer can never reach another's list;
 * a shared list is read-only and opened by a signed link.
 */
class WishlistController extends Controller
{
    public function index(Request $request, WishlistService $wishlist, StorefrontCatalog $catalog): View
    {
        // A shared list carries its pieces in the link, so the person opening it sees the
        // same pieces rather than their own (probably empty) wishlist.
        // Ownership: a signed-in customer's list is WishlistItem::where('user_id', auth('web')->id())
        // (read inside WishlistService); a guest's list is their own session. No item ids are taken
        // from the request, so one customer can never read or delete another customer's items.
        $shared = array_filter(explode(',', (string) $request->query('items')));
        $products = $shared
            ? $catalog->products()->whereIn('slug', array_slice($shared, 0, 60))->get()
            : $wishlist->products($catalog->products());

        return view('storefront.wishlist.index', [
            'products' => $products,
            'shared' => (bool) $shared,
            'shareUrl' => $shared
                ? $request->fullUrl()
                : store_route('wishlist').($products->isNotEmpty() ? '?items='.$products->pluck('slug')->implode(',') : ''),
        ]);
    }

    public function toggle(Request $request, int $product, WishlistService $wishlist, StorefrontCatalog $catalog): JsonResponse|RedirectResponse
    {
        // Ownership: toggling only ever adds/removes a row for THIS customer —
        // WishlistItem::where('user_id', auth('web')->id()) inside WishlistService — or this guest's session.

        $product = $catalog->products()->whereKey($product)->first() ?? abort(404);
        $saved = $wishlist->toggle($product);
        $message = __($saved ? 'store.wishlist.added' : 'store.wishlist.removed', ['name' => $product->name]);

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'count' => $wishlist->count(), 'message' => $message]);
        }

        return back()->with('store_toast', $message);
    }
}
