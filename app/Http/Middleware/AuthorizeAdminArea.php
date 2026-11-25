<?php

namespace App\Http\Middleware;

use App\Enums\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the signed-in admin's role against the page being opened (see AdminRole::AREAS).
 * A deactivated admin is signed out on their next click.
 */
class AuthorizeAdminArea
{
    public function handle(Request $request, Closure $next): Response
    {
        // Authentication first, then the role check. The route group already requires
        // auth:admin; this is the second lock, so the role logic can never run for a
        // visitor who is not signed in.
        if (! Auth::guard('admin')->check()) {
            return $request->expectsJson()
                ? abort(401)
                : redirect()->guest(route('admin.login'));
        }

        $admin = $request->user('admin');

        if ($admin && ! $admin->is_active) {
            \App\Models\AuditLog::record('admin.deactivated_signed_out', 'admin', $admin->id, $admin->email, ['ip' => $request->ip()]);
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->forget('url.intended');   // never send them back to the page they tried

            return redirect()->route('admin.login')->withErrors(['email' => __('admin.admins.deactivated')]);
        }

        $route = $request->route()?->getName();
        $area = AdminRole::areaForRoute($route);

        // Fail closed. A route is allowed only when it is mapped to an area this admin may
        // open, or is one of the few pages every admin may see (their dashboard, their own
        // account, the language switch). Anything unmapped is refused rather than allowed,
        // so a new admin screen cannot ship without a permission.
        if (AdminRole::isOpenToAll($route)) {
            return $next($request);
        }

        if ($area === null || ! $admin->mayAccess($area)) {
            abort(403);   // unmapped routes are refused (fail closed)
        }

        return $next($request);
    }
}
