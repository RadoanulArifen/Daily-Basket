<?php

use App\Http\Controllers\Storefront\CurrencyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes, once per language
|--------------------------------------------------------------------------
|
| routes/storefront.php is registered for every language in config/store.php.
| The default language has no prefix; others are prefixed (/ar/...) and their
| route names too (ar.login). Always link with store_route('login').
|
| Non-default languages are registered first so "/ar" is never mistaken for
| a page slug in the default language.
|
*/

// Plain HTML landing page with its own files: resources/landing/ → /landing/ (not part of the storefront).
Route::get('/landing.html', fn () => redirect()->to(url('landing').'/', 301));
Route::get('/landing/{path?}', \App\Http\Controllers\LandingController::class)->where('path', '.*')->name('landing');

$default = config('store.default_locale');
$locales = collect(config('store.all_locales', config('store.locales')))->keys()
    ->sortBy(fn (string $code) => $code === $default ? 1 : 0);

// Storefront routes (routes/storefront.php), once per language. Inside that file:
//   - /account, /account/orders, /account/addresses … are in Route::middleware('auth:web')->group(...)
//   - cart and wishlist work for guests too (session / signed cookie) and, when signed in,
//     only ever read and write the signed-in customer's own rows.
// The StorefrontPassword middleware below only gates the storefront; it never touches /admin.
foreach ($locales as $locale) {
    $isDefault = $locale === $default;

    Route::middleware(["storefront.locale:{$locale}", \App\Http\Middleware\StorefrontPassword::class, \App\Http\Middleware\StorefrontDemo::class])
        ->prefix($isDefault ? '' : $locale)
        ->name($isDefault ? '' : "{$locale}.")
        ->group(base_path('routes/storefront.php'));
}

// Store password page (Online Store → Preferences)
Route::get('/password', [\App\Http\Controllers\StorePasswordController::class, 'show'])->name('store.password');
Route::post('/password', [\App\Http\Controllers\StorePasswordController::class, 'unlock'])->middleware('throttle:6,1')->name('store.password.unlock');

// Search engines
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');

// Stripe → site notifications (no CSRF token; verified by signature instead).
Route::post('/webhooks/stripe', \App\Http\Controllers\StripeWebhookController::class)->name('webhooks.stripe');

// Language-independent storefront actions.
Route::post('/currency', CurrencyController::class)->name('currency.switch');

// Uploaded images live in storage/app/public and are normally served through the
// public/storage link made by `php artisan storage:link`. On Windows that link often
// cannot be created (it needs administrator rights), which leaves every image broken.
// This serves them through PHP instead, only while the link is missing.
if (! \App\Support\StorageLink::works()) {
    Route::get('/storage/{path}', function (string $path) {
        abort_if(str_contains($path, '..'), 404);
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), ['Cache-Control' => 'public, max-age=31536000']);
    })->where('path', '.*')->name('storage.fallback');
}
