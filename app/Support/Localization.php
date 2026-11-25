<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Storefront language handling.
 *
 * Every storefront route is registered once per language:
 *   English (default): name "products.show", URL /products/{slug}
 *   Arabic:            name "ar.products.show", URL /ar/products/{slug}
 */
class Localization
{
    /**
     * The languages the store actually uses.
     *
     * Every language the code supports is in config; which of them are switched on is the
     * shop owner's choice (Settings → Store details). The default language is always on, so
     * a store can never end up with none.
     *
     * @return array<string, array{name: string, native: string, dir: string}>
     */
    public function locales(): array
    {
        $all = config('store.all_locales', config('store.locales'));

        try {
            $enabled = settings()->get('general.locales');
        } catch (\Throwable) {
            return $all;   // e.g. before the settings table exists
        }

        if (! is_array($enabled) || $enabled === []) {
            return config('store.locales');   // nothing chosen yet: what .env allows
        }

        $enabled = array_values(array_unique([...$enabled, $this->default()]));

        return array_filter($all, fn ($code) => in_array($code, $enabled, true), ARRAY_FILTER_USE_KEY);
    }

    /** Every language the code supports, switched on or not — for the admin's own list. */
    public function supported(): array
    {
        return config('store.all_locales', config('store.locales'));
    }

    public function enabled(string $locale): bool
    {
        return array_key_exists($locale, $this->locales());
    }

    public function codes(): array
    {
        return array_keys($this->locales());
    }

    public function default(): string
    {
        return config('store.default_locale');
    }

    public function current(): string
    {
        return app()->getLocale();
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->locales());
    }

    public function direction(?string $locale = null): string
    {
        return $this->locales()[$locale ?? $this->current()]['dir'] ?? 'ltr';
    }

    public function isRtl(?string $locale = null): bool
    {
        return $this->direction($locale) === 'rtl';
    }

    public function nativeName(string $locale): string
    {
        return $this->locales()[$locale]['native'] ?? $locale;
    }

    /** Route-name prefix for a language: "" for the default, "ar." for Arabic. */
    public function routePrefix(?string $locale = null): string
    {
        $locale ??= $this->current();

        return $locale === $this->default() ? '' : $locale.'.';
    }

    /** "login" -> "ar.login" when the current language is Arabic. */
    public function routeName(string $name, ?string $locale = null): string
    {
        return $this->routePrefix($locale).$this->baseRouteName($name);
    }

    public function route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return route($this->routeName($name, $locale), $parameters, $absolute);
    }

    /** Prefix a storefront path with the language segment: "/about" -> "/ar/about". */
    public function url(string $path, ?string $locale = null): string
    {
        $locale ??= $this->current();
        $path = '/'.ltrim($path, '/');

        if ($locale !== $this->default()) {
            $path = '/'.$locale.($path === '/' ? '' : $path);
        }

        return url($path);
    }

    /** Remove any language prefix from a route name. */
    public function baseRouteName(string $name): string
    {
        foreach ($this->codes() as $code) {
            if (Str::startsWith($name, $code.'.')) {
                return Str::after($name, $code.'.');
            }
        }

        return $name;
    }

    /** Detect the storefront language from a URL's first segment. */
    public function fromRequest(Request $request): string
    {
        $segment = $request->segment(1);

        return $this->isSupported($segment) && $segment !== $this->default() ? $segment : $this->default();
    }

    /**
     * The same page in another language, e.g. for the language switcher.
     */
    public function switchUrl(string $locale, ?Request $request = null): string
    {
        $request ??= request();
        $route = $request->route();

        if ($route && $route->getName()) {
            $target = $this->routeName($route->getName(), $locale);

            if (Route::has($target)) {
                $url = route($target, $route->parameters());
                $query = $request->getQueryString();

                return $query ? $url.'?'.$query : $url;
            }
        }

        return $this->url('/', $locale);
    }
}
