<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, StorefrontCatalog $catalog, string $slug): RedirectResponse
    {
        $product = $catalog->findProduct($slug) ?? abort(404);
        $user = $request->user('web');

        $data = $request->validate([
            'g-recaptcha-response' => [new \App\Rules\Recaptcha],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000'],
        ], [], [
            'photos' => __('store.reviews.photos'),
            'photos.*' => __('store.reviews.photo'),
            'rating' => __('store.reviews.rating'),
            'body' => __('store.reviews.body'),
        ]);

        if (ProductReview::query()->where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['body' => __('store.reviews.already')])->withFragment('reviews');
        }

        // "Verified purchase" when this customer has a non-cancelled order containing the product.
        $bought = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($o) => $o->where('user_id', $user->id)->where('status', '!=', OrderStatus::Cancelled))
            ->exists();

        $photos = $data['photos'] ?? [];
        unset($data['photos']);

        $review = ProductReview::create($data + [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'name' => \Illuminate\Support\Str::limit(\Illuminate\Support\Str::before($user->name, ' ').' '.mb_substr((string) \Illuminate\Support\Str::after($user->name, ' '), 0, 1), 80, ''),
            'status' => 'pending',
            'verified_purchase' => $bought,
            'locale' => app()->getLocale(),
        ]);

        // Photos are re-encoded to WebP (this also strips camera/location data).
        $media = app(\App\Services\Media\MediaService::class);
        foreach (array_values($photos) as $i => $photo) {
            try {
                $review->photos()->attach($media->upload($photo, [], 'reviews')->id, ['position' => $i]);
            } catch (\RuntimeException $e) {
                report($e);
            }
        }

        return back()->with('store_toast', __('store.reviews.thanks'))->withFragment('reviews');
    }
}
