<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Services\Catalog\StorefrontCatalog;
use App\Support\Localization;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    /** Built from the app so the Sitemap line always uses the right domain. */
    public function robots(): Response
    {
        // A password-protected store asks search engines to stay away entirely.
        if (settings()->bool('preferences.password_enabled')) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $admin = trim(config('store.admin_path', 'admin'), '/');
        $lines = ['User-agent: *'];

        foreach (['/'.$admin, '/account', '/cart', '/checkout', '/orders', '/wishlist', '/search'] as $path) {
            $lines[] = 'Disallow: '.$path;
            foreach (app(Localization::class)->codes() as $code) {
                if ($code !== config('store.default_locale')) {
                    $lines[] = 'Disallow: /'.$code.$path;
                }
            }
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Every public page in every language, with hreflang alternates.
     * Cached for an hour; a store has hundreds, not millions, of pages.
     */
    public function sitemap(StorefrontCatalog $catalog, Localization $localization): Response
    {
        $xml = Cache::remember('seo.sitemap', 3600, function () use ($catalog, $localization) {
            $entries = [['path' => '/', 'lastmod' => null], ['path' => '/shop', 'lastmod' => null],
                ['path' => '/collections/new-in', 'lastmod' => null], ['path' => '/collections/sale', 'lastmod' => null]];

            // Only when it is actually indexable — a sitemap entry for a noindex page is a
            // contradiction search engines report as an error.
            if (config('store.landing_indexable')) {
                $entries[] = ['path' => '/landing/', 'lastmod' => null];
            }

            foreach (Category::query()->active()->get(['slug', 'updated_at']) as $c) {
                $entries[] = ['path' => '/collections/'.$c->slug, 'lastmod' => $c->updated_at];
            }
            foreach ($catalog->products()->without(['media', 'variants'])->get(['products.id', 'slug', 'updated_at']) as $p) {
                $entries[] = ['path' => '/products/'.$p->slug, 'lastmod' => $p->updated_at];
            }
            foreach (Page::query()->where('is_active', true)->get(['slug', 'updated_at']) as $p) {
                $entries[] = ['path' => '/pages/'.$p->slug, 'lastmod' => $p->updated_at];
            }

            return view('seo.sitemap', ['entries' => $entries, 'codes' => $localization->codes(), 'localization' => $localization])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
