<?php

namespace App\Http\Middleware;

use App\Support\Localization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel uses the language saved on the admin account
 * (or the one picked on the login screen).
 */
class SetAdminLocale
{
    public function __construct(private Localization $localization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user('admin')?->locale ?? $request->session()->get('admin_locale');

        if (! $this->localization->isSupported($locale)) {
            $locale = $this->localization->default();
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
