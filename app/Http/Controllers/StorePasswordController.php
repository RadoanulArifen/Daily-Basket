<?php

// SECURITY: PUBLIC by design — the shopper-facing pre-launch password page (not an admin page).
// It never grants admin access; admin routes always need auth:admin. Unlock is throttled (6/min)
// and compared with Hash::check(). Documented in SECURITY.md.

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * The PUBLIC "store is password-protected" page shown to shoppers before launch (Online Store →
 * Preferences → Password). It is not an admin page and never grants admin access: a correct
 * password only sets a storefront session flag checked by the StorefrontPassword middleware on
 * storefront routes. Admin routes (routes/admin.php) are separate and always need auth:admin.
 * Unlocking is rate-limited (6 tries a minute) and compared with Hash::check().
 */
class StorePasswordController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [new \Illuminate\Routing\Controllers\Middleware('throttle:6,1', only: ['unlock'])];
    }

    public function show(): View|RedirectResponse
    {
        if (! settings()->bool('preferences.password_enabled')) {
            return redirect(store_route('home'));
        }

        return view('storefront.password', ['message' => settings()->trans('preferences.password_message')]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:100']]);

        if (! Hash::check($request->input('password'), (string) settings()->get('preferences.password_hash'))) {
            return back()->withErrors(['password' => __('store.password_page.wrong')]);
        }

        $request->session()->regenerate();
        $request->session()->put('store_unlocked', true);

        return redirect()->intended(store_route('home'));
    }
}
