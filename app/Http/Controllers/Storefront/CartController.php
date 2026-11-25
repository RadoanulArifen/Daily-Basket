<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(CartService $cart): View
    {
        return view('storefront.cart.show', ['summary' => $cart->summary()]);
    }

    /** The slide-out bag's contents (themes that have one), loaded when it opens. */
    public function drawer(CartService $cart): \Illuminate\Http\Response
    {
        abort_unless(view()->exists('storefront.cart.drawer'), 404);

        return response()->view('storefront.cart.drawer', ['summary' => $cart->summary()])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function store(AddToCartRequest $request, CartService $cart): JsonResponse|RedirectResponse
    {
        $variant = ProductVariant::query()->with('product')->find($request->integer('variant_id'));

        if (! $variant) {
            throw \Illuminate\Validation\ValidationException::withMessages(['variant_id' => __('store.cart.unavailable')]);
        }

        // "Configure your piece": only choices the product actually offers are kept.
        $offered = collect((array) $variant->product->configuration)->mapWithKeys(fn ($o) => [$o['label'] => (array) $o['values']]);
        $options = collect((array) $request->input('options', []))
            ->filter(fn ($value, $label) => is_string($value) && in_array($value, $offered->get($label, []), true))->all();

        $cart->add($variant, $request->integer('quantity', 1) ?: 1, $options);
        $message = __('store.cart.added', ['name' => $variant->product->name]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'count' => $cart->count(), 'cart_url' => store_route('cart')]);
        }

        return back()->with('store_toast', $message);
    }

    public function update(Request $request, CartItem $item, CartService $cart): JsonResponse|RedirectResponse
    {
        abort_unless($item->cart_id === $cart->current()?->id, 404);   // ownership: only a line of this visitor's own cart
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_LINE_QUANTITY]]);

        $cart->update($item, (int) $data['quantity']);

        return $request->expectsJson()
            ? response()->json(['count' => $cart->count()])
            : redirect(store_route('cart'));
    }

    public function destroy(Request $request, CartItem $item, CartService $cart): JsonResponse|RedirectResponse
    {
        abort_unless($item->cart_id === $cart->current()?->id, 404);   // ownership: only a line of this visitor's own cart
        $cart->remove($item);

        return $request->expectsJson()
            ? response()->json(['count' => $cart->count()])
            : redirect(store_route('cart'))->with('store_toast', __('store.cart.removed'));
    }

    public function applyDiscount(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $cart->applyDiscount($data['code']);

        return back()->with('store_toast', __('store.discount.applied', ['code' => mb_strtoupper(trim($data['code']))]));
    }

    public function removeDiscount(CartService $cart): RedirectResponse
    {
        $cart->removeDiscount();

        return back();
    }
}
