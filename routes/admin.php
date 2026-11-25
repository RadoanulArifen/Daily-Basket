<?php

/*
| Admin routes. SECURITY: every page below the sign-in/reset group sits inside
|   Route::middleware(['auth:admin', AuthorizeAdminArea::class])->group(...)
| (auth first, then the role check against AdminRole::AREAS; unmapped routes are refused).
| Every admin controller also extends AdminController, which declares the same two middleware,
| so a route added outside the group would still be protected.
*/

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Catalog\CategoryController;
use App\Http\Controllers\Admin\Content\BannerController;
use App\Http\Controllers\Admin\Insights\ActivityController;
use App\Http\Controllers\Admin\Insights\AnalyticsController;
use App\Http\Controllers\Admin\Insights\CustomerController;
use App\Http\Controllers\Admin\Content\MenuController;
use App\Http\Controllers\Admin\Content\PageController;
use App\Http\Controllers\Admin\Marketing\DiscountController;
use App\Http\Controllers\Admin\Marketing\ReviewController;
use App\Http\Controllers\Admin\Sales\OrderController;
use App\Http\Controllers\Admin\Team\AdminUserController;
use App\Http\Controllers\Admin\Settings\NotificationSettingsController;
use App\Http\Controllers\Admin\Settings\TrackingSettingsController;
use App\Http\Controllers\Admin\Settings\PaymentSettingsController;
use App\Http\Controllers\Admin\Settings\ShippingSettingsController;
use App\Http\Controllers\Admin\Catalog\InventoryController;
use App\Http\Controllers\Admin\Catalog\MediaController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\Settings\CheckoutSettingsController;
use App\Http\Controllers\Admin\Settings\CurrencyController;
use App\Http\Controllers\Admin\Settings\GeneralSettingsController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\TaxSettingsController;
use Illuminate\Support\Facades\Route;

/*
| Admin panel. Prefix (/admin), name prefix (admin.) and the
| admin.locale + admin.headers middleware are applied in bootstrap/app.php.
*/

Route::post('/locale', LocaleController::class)->name('locale');

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/login/demo/{role}', [LoginController::class, 'demo'])->middleware('throttle:20,1')->name('login.demo');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:forms')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth:admin', \App\Http\Middleware\AuthorizeAdminArea::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    // Guided tours: the signed-in admin's own tour progress (open to every admin; each tour is
    // limited to the admin's role areas in the controller).
    Route::post('/tours', [\App\Http\Controllers\Admin\Help\TourController::class, 'update'])->middleware('throttle:120,1')->name('tours.update');
    Route::get('/search', \App\Http\Controllers\Admin\SearchController::class)->middleware('throttle:120,1')->name('search');
    Route::post('/views', [\App\Http\Controllers\Admin\SavedViewController::class, 'store'])->name('views.store');
    Route::delete('/views/{view}', [\App\Http\Controllers\Admin\SavedViewController::class, 'destroy'])->name('views.destroy');

    // Made-to-order enquiries
    Route::get('/enquiries', [\App\Http\Controllers\Admin\Sales\EnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [\App\Http\Controllers\Admin\Sales\EnquiryController::class, 'show'])->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [\App\Http\Controllers\Admin\Sales\EnquiryController::class, 'update'])->name('enquiries.update');

    // Returns (before orders/{order}, so "returns" isn't read as an order id)
    Route::get('/returns', [\App\Http\Controllers\Admin\Sales\ReturnController::class, 'index'])->name('returns.index');
    Route::put('/returns/settings', [\App\Http\Controllers\Admin\Sales\ReturnController::class, 'settings'])->name('returns.settings');
    Route::get('/returns/{return}', [\App\Http\Controllers\Admin\Sales\ReturnController::class, 'show'])->name('returns.show');
    Route::put('/returns/{return}', [\App\Http\Controllers\Admin\Sales\ReturnController::class, 'update'])->name('returns.update');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/bulk', [OrderController::class, 'bulk'])->name('orders.bulk');
    Route::get('/orders/create', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'create'])->name('draft-orders.create');
    Route::post('/orders/create', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'store'])->name('draft-orders.store');
    Route::get('/orders/create/variants', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'variants'])->name('draft-orders.variants');
    Route::get('/orders/drafts', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'drafts'])->name('draft-orders.drafts');
    Route::post('/orders/drafts', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'saveDraft'])->name('draft-orders.save-draft');
    Route::delete('/orders/drafts/{draft}', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'destroyDraft'])->name('draft-orders.destroy-draft');
    Route::get('/orders/create/customers', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'customers'])->name('draft-orders.customers');
    Route::get('/orders/create/rates', [\App\Http\Controllers\Admin\Sales\DraftOrderController::class, 'rates'])->name('draft-orders.rates');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    // Book an Aramex shipment for an order, and reprint its label.
    Route::post('/orders/{order}/shipment', [\App\Http\Controllers\Admin\Sales\ShipmentController::class, 'store'])->name('orders.shipment');
    Route::get('/fulfillments/{fulfillment}/label', [\App\Http\Controllers\Admin\Sales\ShipmentController::class, 'label'])->name('fulfillments.label');

    Route::post('/orders/{order}/email', [OrderController::class, 'email'])->middleware('throttle:20,1')->name('orders.email');
    Route::post('/orders/{order}/paid', [OrderController::class, 'markPaid'])->name('orders.paid');
    Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('/orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::post('/orders/{order}/note', [OrderController::class, 'note'])->name('orders.note');
    Route::get('/orders/{order}/fulfill', [\App\Http\Controllers\Admin\Sales\OrderFlowController::class, 'fulfillForm'])->name('orders.fulfill');
    Route::post('/orders/{order}/fulfillments', [\App\Http\Controllers\Admin\Sales\OrderFlowController::class, 'fulfill'])->name('orders.fulfillments.store');
    Route::post('/fulfillments/{fulfillment}/delivered', [\App\Http\Controllers\Admin\Sales\OrderFlowController::class, 'delivered'])->name('fulfillments.delivered');
    Route::get('/orders/{order}/refunds/new', [\App\Http\Controllers\Admin\Sales\OrderFlowController::class, 'refundForm'])->name('orders.refunds.create');
    Route::post('/orders/{order}/refunds', [\App\Http\Controllers\Admin\Sales\OrderFlowController::class, 'refund'])->name('orders.refunds.store');

    // Admin accounts (owner only — see AdminRole)
    Route::resource('admins', AdminUserController::class)->except('show');
    Route::post('/admins/{admin}/invite', [AdminUserController::class, 'resend'])->name('admins.resend');

    // Online Store: themes, custom code, preferences
    Route::prefix('online-store')->name('online-store.')->controller(\App\Http\Controllers\Admin\OnlineStore\OnlineStoreController::class)->group(function () {
        Route::get('/', 'themes')->name('themes');
        Route::get('/customize', 'customize')->name('customize');
        Route::put('/customize', 'saveDraft')->name('customize.save');
        Route::post('/customize/publish', 'publish')->name('customize.publish');
        Route::delete('/customize', 'discard')->name('customize.discard');
        Route::get('/preview', 'previewStart')->name('preview.start');
        Route::get('/preview/exit', 'previewExit')->name('preview.exit');
        Route::get('/code', 'code')->name('code');
        Route::put('/code', 'saveCode')->name('code.save');
        Route::post('/code/versions/{version}', 'restoreCode')->name('code.restore');
        Route::get('/preferences', 'preferences')->name('preferences');
        Route::put('/preferences', 'savePreferences')->name('preferences.save');
    });

    // Customers, analytics, activity
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');
    Route::put('/customers/{customer}/note', [CustomerController::class, 'note'])->name('customers.note');
    Route::get('/analytics', AnalyticsController::class)->name('analytics');
    require __DIR__.'/admin-traffic.php';   // Analytics → Traffic
    Route::get('/activity', ActivityController::class)->name('activity');

    // Marketing
    Route::resource('discounts', DiscountController::class)->except('show');
    Route::resource('promotions', \App\Http\Controllers\Admin\Marketing\PromotionController::class)->except('show');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Content
    Route::resource('pages', PageController::class)->except('show');
    Route::resource('banners', BannerController::class)->except('show');
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
    Route::get('/menus/{menu}', [MenuController::class, 'edit'])->name('menus.edit');
    Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
    Route::put('/menus/{menu}/items/{item}', [MenuController::class, 'updateItem'])->name('menus.items.update');
    Route::delete('/menus/{menu}/items/{item}', [MenuController::class, 'destroyItem'])->name('menus.items.destroy');

    // Catalog
    Route::post('/products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
    Route::get('/products/export', [\App\Http\Controllers\Admin\Catalog\ProductTransferController::class, 'export'])->name('products.export');
    Route::get('/products/import', [\App\Http\Controllers\Admin\Catalog\ProductTransferController::class, 'create'])->name('products.import');
    Route::get('/products/import/template', [\App\Http\Controllers\Admin\Catalog\ProductTransferController::class, 'template'])->name('products.import.template');
    Route::post('/products/import/check', [\App\Http\Controllers\Admin\Catalog\ProductTransferController::class, 'preview'])->name('products.import.preview');
    Route::post('/products/import', [\App\Http\Controllers\Admin\Catalog\ProductTransferController::class, 'store'])->name('products.import.store');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', \App\Http\Controllers\Admin\Catalog\BrandController::class)->except('show');

    // Newsletter subscribers
    Route::get('/subscribers', [\App\Http\Controllers\Admin\Marketing\SubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/subscribers/export', [\App\Http\Controllers\Admin\Marketing\SubscriberController::class, 'export'])->name('subscribers.export');
    Route::delete('/subscribers/{subscriber}', [\App\Http\Controllers\Admin\Marketing\SubscriberController::class, 'destroy'])->name('subscribers.destroy');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:60,1')->name('media.store');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::put('/inventory/threshold', [InventoryController::class, 'threshold'])->name('inventory.threshold');
    Route::post('/inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', SettingsController::class)->name('index');
        require __DIR__.'/admin-backup.php';   // Backup & clean-up

        Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
        Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');

        Route::get('/tax', [TaxSettingsController::class, 'edit'])->name('tax.edit');
        Route::put('/tax', [TaxSettingsController::class, 'update'])->name('tax.update');

        Route::get('/checkout', [CheckoutSettingsController::class, 'edit'])->name('checkout.edit');
        Route::put('/checkout', [CheckoutSettingsController::class, 'update'])->name('checkout.update');

        Route::get('/shipping', [ShippingSettingsController::class, 'edit'])->name('shipping.edit');
        Route::put('/shipping', [ShippingSettingsController::class, 'update'])->name('shipping.update');

        Route::get('/emails/{event}/preview', [\App\Http\Controllers\Admin\Settings\EmailPreviewController::class, 'show'])->name('emails.preview');
        Route::post('/emails/{event}/send', [\App\Http\Controllers\Admin\Settings\EmailPreviewController::class, 'send'])->middleware('throttle:10,1')->name('emails.send');

        Route::get('/mail-server', [\App\Http\Controllers\Admin\Settings\MailServerController::class, 'edit'])->name('mail-server.edit');
        Route::post('/mail-server/run-queue', [\App\Http\Controllers\Admin\Settings\MailServerController::class, 'runQueue'])->middleware('throttle:5,1')->name('mail-server.run-queue');

        Route::put('/credentials/{group}', [\App\Http\Controllers\Admin\Settings\CredentialsController::class, 'update'])->whereIn('group', ['stripe', 'aramex', 'smtp', 'recaptcha'])->name('credentials.update');
        Route::post('/credentials/{group}/test', [\App\Http\Controllers\Admin\Settings\CredentialsController::class, 'test'])->whereIn('group', ['stripe', 'aramex', 'smtp'])->middleware('throttle:10,1')->name('credentials.test');
        Route::delete('/credentials/{group}', [\App\Http\Controllers\Admin\Settings\CredentialsController::class, 'forget'])->whereIn('group', ['stripe', 'aramex', 'smtp', 'recaptcha'])->name('credentials.forget');
        Route::put('/shipping/aramex', [\App\Http\Controllers\Admin\Settings\CredentialsController::class, 'toggleAramex'])->name('shipping.aramex');

        Route::get('/tracking', [TrackingSettingsController::class, 'edit'])->name('tracking.edit');
        Route::put('/tracking', [TrackingSettingsController::class, 'update'])->name('tracking.update');

        Route::get('/notifications', [NotificationSettingsController::class, 'edit'])->name('notifications.edit');
        Route::put('/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

        Route::get('/payments', [PaymentSettingsController::class, 'edit'])->name('payments.edit');
        Route::put('/payments', [PaymentSettingsController::class, 'update'])->name('payments.update');

        Route::resource('currencies', CurrencyController::class)->except('show');
        // Switching a currency on or off, and changing which one prices are stored in.
        Route::post('currencies/{currency}/toggle', [CurrencyController::class, 'toggle'])->name('currencies.toggle');
        Route::post('currencies/{currency}/make-base', [CurrencyController::class, 'makeBase'])->name('currencies.make-base');
    });
});
