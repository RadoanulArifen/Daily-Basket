<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Admin\AdminController;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends AdminController
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]])['status'] ?? 'pending';

        return view('admin.marketing.reviews.index', [
            'reviews' => ProductReview::query()->where('status', $status)->with(['product', 'photos'])->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'pendingCount' => ProductReview::query()->where('status', 'pending')->count(),
        ]);
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $review->update($data);
        ProductReview::refreshProductRating($review->product_id);

        return back()->with('success', __("admin.reviews.{$data['status']}"));
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $media = app(\App\Services\Media\MediaService::class);
        foreach ($review->photos as $photo) {
            $media->delete($photo);
        }
        $review->delete();
        ProductReview::refreshProductRating($review->product_id);

        return back()->with('success', __('admin.reviews.deleted'));
    }
}
