<?php

namespace App\Http\Middleware;

use App\Support\Localization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language a storefront route group was registered for.
 * Usage: ->middleware('storefront.locale:ar')
 */
class SetStorefrontLocale
{
    public function __construct(private Localization $localization) {}

    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        // A language that has been switched off: send visitors (and search engines) to the
        // same page in the store's own language rather than showing it anyway.
        if ($locale !== null && $locale !== $this->localization->default() && ! $this->localization->enabled($locale)) {
            $path = ltrim(preg_replace('#^/'.preg_quote($locale, '#').'(/|$)#', '/', '/'.ltrim($request->path(), '/')), '/');

            return redirect()->to(url($path).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        $locale = $this->localization->isSupported($locale) ? $locale : $this->localization->default();

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        // Remember the choice so emails and redirects outside the storefront use it too.
        if ($request->hasSession()) {
            $request->session()->put('storefront_locale', $locale);
        }

        return $next($request);
    }
}
