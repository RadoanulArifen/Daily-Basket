<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthorizeAdminArea;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Base class for every admin screen.
 *
 * The admin routes already run inside an `auth:admin` group (routes/admin.php), and this
 * declares the same guard on the controller itself, so the protection is visible wherever
 * you open the code and survives a route file being reorganised.
 *
 * `AuthorizeAdminArea` then checks the signed-in admin's role against the area being opened.
 */
abstract class AdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:admin'),
            new Middleware(AuthorizeAdminArea::class),
        ];
    }

    /**
     * Role check inside an action (a second layer on top of the AuthorizeAdminArea middleware on
     * the route): the signed-in admin's role must allow $area, otherwise 403.
     */
    protected function authorizeArea(string $area): void
    {
        abort_unless(auth('admin')->user()?->mayAccess($area), 403);
    }
}
