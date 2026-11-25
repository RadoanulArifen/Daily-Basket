<?php

use App\Http\Controllers\Storefront\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Storefront\Auth\ForgotPasswordController;
use App\Http\Controllers\Storefront\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController;
use App\Http\Controllers\Storefront\Auth\ResetPasswordController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

/*
| Loaded once per language by routes/web.php — see the note there.
*/

Route::get('/', HomeController::class)->name('home');

// Catalog
Route::get('/shop', [CollectionController::class, 'all'])->name('shop');
// The design calls the sale page "Offers"; both addresses work.
Route::redirect('/offers', '/collections/sale')->name('offers');

Route::get('/collections/{slug}', [CollectionController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('collections.show');
Route::get('/products/{slug}', [ProductController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('products.show');
Route::get('/search', SearchController::class)->name('search');

// Content pages (About, Contact, policies)
Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');
// Custom Order (bespoke pieces)
Route::get('/custom-order', [\App\Http\Controllers\Storefront\CustomOrderController::class, 'show'])->name('custom-order');
Route::post('/custom-order', [\App\Http\Controllers\Storefront\CustomOrderController::class, 'store'])->middleware('throttle:forms')->name('custom-order.store');

Route::post('/pages/{slug}/contact', [PageController::class, 'contact'])->where('slug', '[a-z0-9-]+')->middleware('throttle:forms')->name('pages.contact');

// Bag and wishlist (guests too)
Route::get('/cart', [CartController::class, 'show'])->name('cart');
Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('cart.remove');
    Route::post('/cart/discount', [CartController::class, 'applyDiscount'])->name('cart.discount');
    Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])->name('cart.discount.remove');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->whereNumber('product')->name('wishlist.toggle');
});
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');

// Checkout and order pages (guests reach their order by its private link)
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::post('/checkout/email', [\App\Http\Controllers\Storefront\ReminderController::class, 'rememberEmail'])->middleware('throttle:20,1')->name('checkout.email');
Route::get('/cart/restore/{cart}', [\App\Http\Controllers\Storefront\ReminderController::class, 'restore'])->middleware('signed')->whereNumber('cart')->name('cart.restore');
Route::get('/emails/unsubscribe', [\App\Http\Controllers\Storefront\ReminderController::class, 'unsubscribe'])->middleware('signed')->name('emails.unsubscribe');
Route::get('/brands/{slug}', [\App\Http\Controllers\Storefront\BrandController::class, 'show'])->name('brands.show');
Route::post('/newsletter', [\App\Http\Controllers\Storefront\NewsletterController::class, 'store'])->middleware('throttle:6,1')->name('newsletter.subscribe');
Route::get('/checkout/complete/{token}', [CheckoutController::class, 'complete'])->name('checkout.complete');
Route::get('/orders/{token}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{token}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
Route::get('/orders/{token}/return', [\App\Http\Controllers\Storefront\ReturnController::class, 'create'])->name('returns.create');
Route::post('/orders/{token}/return', [\App\Http\Controllers\Storefront\ReturnController::class, 'store'])->middleware('throttle:forms')->name('returns.store');
Route::post('/orders/{token}/pay', [OrderController::class, 'pay'])->middleware('throttle:10,1')->name('orders.pay');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/login/demo', [LoginController::class, 'demo'])->middleware('throttle:20,1')->name('login.demo');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:forms')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

// Customer account area: every /account/* route requires a signed-in customer (auth:web).
// Records inside are always loaded for that customer only (see SECURITY.md).
Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account', AccountDashboardController::class)->name('account.dashboard');
    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders');
    // Put a past order's pieces back in the bag, at today's prices.
    Route::post('/account/orders/{order}/reorder', [OrderController::class, 'reorder'])
        ->middleware('throttle:20,1')->name('account.orders.reorder');

    Route::get('/account/addresses', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'index'])->name('account.addresses');
    Route::get('/account/addresses/new', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'create'])->name('account.addresses.create');
    Route::post('/account/addresses', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'store'])->name('account.addresses.store');
    Route::get('/account/addresses/{address}/edit', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'edit'])->whereNumber('address')->name('account.addresses.edit');
    Route::put('/account/addresses/{address}', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'update'])->whereNumber('address')->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'destroy'])->whereNumber('address')->name('account.addresses.destroy');
    Route::post('/account/addresses/{address}/default', [\App\Http\Controllers\Storefront\Account\AddressController::class, 'makeDefault'])->whereNumber('address')->name('account.addresses.default');
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:forms')->name('reviews.store');
});

// Live results for the header search box.
Route::get('/search/suggest', [\App\Http\Controllers\Storefront\SearchController::class, 'suggest'])
    ->middleware('throttle:60,1')->name('search.suggest');

// "Tell me when it is back" — asking, and the link that stops it.
Route::post('/stock-alerts', [\App\Http\Controllers\Storefront\StockAlertController::class, 'store'])
    ->middleware('throttle:10,1')->name('stock-alerts.store');
Route::get('/stock-alerts/{token}/cancel', [\App\Http\Controllers\Storefront\StockAlertController::class, 'destroy'])
    ->middleware('throttle:20,1')->name('stock-alerts.cancel');
