<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorefrontDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('storefront.demo_preview')
            && ! $request->isMethodSafe()
            && ! $request->routeIs('logout', '*.logout')) {
            abort(403, __('store.auth.demo_read_only'));
        }

        return $next($request);
    }
}
