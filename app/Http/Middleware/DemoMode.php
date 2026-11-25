<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only admin, for a public demo.
 *
 * Switched on with DEMO_MODE=true. Visitors can open every screen and see real data, but
 * nothing they do is saved: anything that writes is turned back with a message. Signing in
 * and out still work, or nobody could get in at all.
 *
 * Off by default, so a shop that buys this runs with a normal admin and never sees it.
 */
class DemoMode
{
    /** Writes that must keep working, or the demo is unusable. */
    private const ALLOWED = [
        'admin.login',            // signing in
        'admin.login.store',
        'admin.login.demo',
        'admin.logout',
        'admin.locale.update',    // switching the admin's language
        'admin.online-store.preview.start',
        'admin.online-store.preview.stop',
        'admin.tours.update',     // guided-tour progress (kept in the visitor's session on a demo)
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $demo = config('store.demo');
        if (! $demo && ! $request->session()->get('admin.demo_preview')) {
            return $next($request);
        }

        if ($request->isMethodSafe() || in_array($request->route()?->getName(), self::ALLOWED, true)) {
            config(['store.demo' => true]);

            try {
                return $next($request);
            } finally {
                config(['store.demo' => $demo]);
            }
        }

        $message = __('admin.demo.blocked');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
