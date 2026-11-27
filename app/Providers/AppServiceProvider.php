<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Catalog\StorefrontCatalog;
use App\Services\Currency\CurrencyService;
use App\Services\Navigation\MenuRepository;
use App\Services\Settings\SettingsService;
use App\Services\Wishlist\WishlistService;
use App\Support\Localization;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(CurrencyService::class);
        $this->app->singleton(MenuRepository::class);
        $this->app->singleton(Localization::class);

        // Per-request state (the visitor's cart and wishlist).
        $this->app->scoped(CartService::class);
        $this->app->scoped(WishlistService::class);
        $this->app->scoped(StorefrontCatalog::class);
        $this->app->scoped(\App\Themes\Freshnest\HomeData::class);
        $this->app->scoped(\App\Services\Promotions\PromotionService::class);
        $this->app->singleton(\App\Services\Settings\Credentials::class);
    }

    public function boot(): void
    {
        // Catch N+1 queries, typos in attribute names and silent mass-assignment
        // drops while developing. Production is unaffected.
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->letters()->numbers()->symbols()->uncompromised()
            : Password::min(8));

        // Customer password-reset links open in the customer's language.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return app(Localization::class)->route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], $user->preferredLocale());
        });

        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // A guest's bag and wishlist move to their account when they sign in or register.
        Event::listen(Login::class, function (Login $event) {
            if ($event->guard === 'web') {
                app(CartService::class)->mergeGuestCartInto($event->user);
                app(WishlistService::class)->mergeGuestListInto($event->user);
            }
        });

        // A changed product, category or page shows up in the sitemap straight away.
        foreach ([\App\Models\Product::class, \App\Models\Category::class, \App\Models\Page::class] as $model) {
            $model::saved(fn () => \Illuminate\Support\Facades\Cache::deleteMultiple(['seo.sitemap', 'home.category_counts']));
            $model::deleted(fn () => \Illuminate\Support\Facades\Cache::deleteMultiple(['seo.sitemap', 'home.category_counts']));
        }

        if (config('cache.default') === 'file' && config('store.theme') === 'freshnest') {
            foreach ([
                \App\Models\Product::class,
                \App\Models\ProductVariant::class,
                \App\Models\Category::class,
                \App\Models\ProductOptionValue::class,
                \App\Models\Brand::class,
                \App\Models\Media::class,
            ] as $model) {
                $events = ['eloquent.saved: '.$model, 'eloquent.deleted: '.$model, 'eloquent.restored: '.$model];
                Event::listen($events, fn () => \Illuminate\Support\Facades\DB::afterCommit(
                    fn () => \Illuminate\Support\Facades\Cache::forget(\App\Themes\Freshnest\HomeData::HOMEPAGE_CATALOG_CACHE),
                ));
            }
        }

        if (config('cache.default') === 'file' && config('store.theme') === 'freshnest') {
            foreach ([
                \App\Models\Banner::class,
                \App\Models\Category::class,
                \App\Models\Discount::class,
                \App\Models\Media::class,
                \App\Models\Promotion::class,
            ] as $model) {
                Event::listen(['eloquent.saved: '.$model, 'eloquent.deleted: '.$model], fn () => \Illuminate\Support\Facades\DB::afterCommit(
                    fn () => \Illuminate\Support\Facades\Cache::forget(\App\Themes\Freshnest\HomeData::HOMEPAGE_CONTENT_CACHE),
                ));
            }
        }

        // Storefront theme (STORE_THEME): its views win, the base views fill any gaps.
        if (($theme = config('store.theme')) && is_dir($path = resource_path("themes/{$theme}/views"))) {
            \Illuminate\Support\Facades\View::prependLocation($path);
        }

        // Stripe / Aramex details saved in the admin (encrypted) — .env values win.
        app(\App\Services\Settings\Credentials::class)->apply();

        $this->shareStorefrontData();
    }

    private function shareStorefrontData(): void
    {
        View::composer('storefront.layouts.*', function ($view) {
            $view->with([
                'storeMenus' => app(MenuRepository::class)->all(),
                'storeCurrencies' => app(CurrencyService::class)->active(),
                'currentCurrency' => app(CurrencyService::class)->current(),
                'cartCount' => app(CartService::class)->count(),
                'wishlistCount' => app(WishlistService::class)->count(),
            ]);
        });
    }
}
