<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\EmailOptOut;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class ReminderController extends Controller
{
    /** Checkout page: remember the email typed so far (for a possible reminder). */
    public function rememberEmail(Request $request, CartService $cart): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $cart->rememberEmail($data['email']);

        return response()->json(['ok' => true]);
    }

    /** Link in the reminder email: brings the bag back, even on another device. */
    public function restore(Cart $cart): RedirectResponse
    {
        if ($cart->user_id) {
            // A customer's bag lives on their account: they just need to sign in.
            return redirect(store_route('cart'));
        }

        Cookie::queue(Cookie::make(CartService::COOKIE, $cart->token, CartService::COOKIE_MINUTES, httpOnly: true, sameSite: 'lax'));

        return redirect(store_route('cart'));
    }

    public function unsubscribe(Request $request): View
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 404);
        EmailOptOut::query()->firstOrCreate(['email' => $email]);

        return view('storefront.unsubscribed', ['email' => $email]);
    }
}
