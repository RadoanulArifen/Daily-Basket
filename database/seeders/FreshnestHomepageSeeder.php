<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Services\Theme\ThemeSettings;
use Illuminate\Database\Seeder;

/**
 * Adds the FreshNest homepage content (banners, deals, discount codes) and the Shop page's
 * headline, and the product pages' nutrition, storage tips and reviews, to a FreshNest store
 * that was set up before the homepage existed:
 *   php artisan db:seed --class=FreshnestHomepageSeeder
 * Does nothing if the homepage banners are already there.
 */
class FreshnestHomepageSeeder extends Seeder
{
    public function run(): void
    {
        // The Shop page's headline and intro (Content → Pages → shop; hidden as a page).
        foreach (['shop' => 'shop', 'sale' => 'offers', 'offers' => 'offers'] as $slug => $key) {
            $meta = FreshnestSeeder::data()['PAGES'][$key];
            \App\Models\Page::query()->withoutGlobalScopes()->firstOrCreate(['slug' => $slug], [
                'title' => ['en' => $meta['title']], 'intro' => ['en' => $meta['blurb']], 'is_active' => $slug === 'offers',
            ]);
        }
        // The Offers page is now its own page (/pages/offers) in the design's layout.
        \App\Models\MenuItem::query()->where('url', '/offers')->update(['url' => '/pages/offers']);
        \App\Models\Banner::query()->where('url', '/offers')->update(['url' => '/pages/offers']);
        app(\App\Services\Navigation\MenuRepository::class)->flush();

        // Dollars as the base currency (a store seeded before this fix still had the dirham
        // marked "Base"); other currencies' rates are then against the dollar.
        if (\App\Models\Currency::query()->where('code', 'AED')->where('is_base', true)->exists()) {
            FreshnestSeeder::currencies();
        }

        // Default country for address forms (stores upgraded to country + state addresses).
        if (blank(settings()->get('shipping.default_country'))) {
            settings()->setMany('shipping', ['default_country' => 'US']);
        }

        // The FreshNest logo (admin sign-in, admin, emails) if none is uploaded yet.
        FreshnestSeeder::logo(app(\App\Services\Settings\SettingsService::class));

        // Arabic for the demo content, where the Arabic text is still empty.
        FreshnestSeeder::arabic(FreshnestSeeder::data());

        // Product pages: nutrition, storage tips and reviews where still missing.
        app(FreshnestSeeder::class)->productDetails(FreshnestSeeder::data());

        if (Banner::query()->whereIn('placement', ThemeSettings::BANNER_PLACEMENTS['freshnest'])->exists()) {
            $this->command?->info('FreshNest homepage content is already there — nothing added.');

            return;
        }

        Banner::query()->where('placement', 'hero')->delete(); // the base demo's hero, if any
        app(FreshnestSeeder::class)->homepage(FreshnestSeeder::data());
        \App\Models\Product::query()->whereIn('slug', ['heirloom-tomatoes'])->update(['is_featured' => true]);
        $this->command?->info('FreshNest homepage content added.');
    }
}
