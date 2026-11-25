<?php

// SECURITY: gates STOREFRONT routes only (routes/web.php → routes/storefront.php); it is never
// applied to routes/admin.php and cannot unlock or bypass admin authentication.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Online Store → Preferences → "Password protect the store" (e.g. before launch).
 * Visitors see the password page; signed-in admins pass straight through.
 * Storefront routes only (routes/web.php): it never unlocks or guards admin routes, which always
 * require auth:admin + AuthorizeAdminArea (routes/admin.php).
 */
class StorefrontPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $locked = settings()->bool('preferences.password_enabled') && filled(settings()->get('preferences.password_hash'));
        } catch (Throwable) {
            $locked = false;
        }

        if ($locked && ! auth('admin')->check() && ! $request->session()->get('store_unlocked')) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('store.password');
        }

        return $next($request);
    }
}
