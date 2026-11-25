<?php

use App\Http\Middleware\AdminResponseHeaders;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetStorefrontLocale;
use App\Support\Localization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'admin.locale', 'admin.headers', \App\Http\Middleware\DemoMode::class])
                ->prefix(config('store.admin_path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'storefront.locale' => SetStorefrontLocale::class,
            'admin.locale' => SetAdminLocale::class,
            'admin.headers' => AdminResponseHeaders::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Analytics → Traffic: counts storefront visits after the page is sent (never breaks a page).
        $middleware->appendToGroup('web', \App\Http\Middleware\RecordTraffic::class);

        // Stripe webhooks can't send a CSRF token; they are verified by signature instead.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Where to send visitors who are not logged in.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->routeIs('admin.*')) {
                return route('admin.login');
            }

            $localization = app(Localization::class);

            return $localization->route('login', [], $localization->fromRequest($request));
        });

        // Where to send people who are already logged in (e.g. visiting /login).
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->routeIs('admin.*')) {
                return route('admin.dashboard');
            }

            $localization = app(Localization::class);

            return $localization->route('account.dashboard', [], $localization->fromRequest($request));
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
