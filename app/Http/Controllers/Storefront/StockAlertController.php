<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Rules\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Tell me when it is back": a visitor asking to hear about one variant. */
class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'email:filter', 'max:190'],
            'g-recaptcha-response' => [new Recaptcha],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['variant_id']);

        // Nothing to wait for if it is already there.
        if ($variant->isInStock()) {
            return back()->with('error', __('store.stock_alert.already_in_stock'));
        }

        StockAlert::query()->firstOrCreate(
            ['product_variant_id' => $variant->id, 'email' => mb_strtolower($data['email'])],
            ['user_id' => $request->user('web')?->id, 'locale' => app()->getLocale()],
        );

        return back()->with('success', __('store.stock_alert.saved'));
    }

    /** The "stop telling me" link at the bottom of the email. */
    public function destroy(string $token): RedirectResponse
    {
        // The token is a long random value made when the alert is created (never a number
        // or an id), the route is rate-limited, and a wrong token simply deletes nothing.
        abort_unless(strlen($token) >= 32 && ctype_alnum($token), 404);
        StockAlert::query()->where('token', $token)->delete();

        return redirect()->to(store_route('home'))->with('success', __('store.stock_alert.cancelled'));
    }
}
