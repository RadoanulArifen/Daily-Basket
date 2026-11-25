<?php

use App\Services\Currency\CurrencyService;
use App\Services\Settings\SettingsService;
use App\Support\Localization;
use App\Support\Money;

if (! function_exists('settings')) {
    function settings(): SettingsService
    {
        return app(SettingsService::class);
    }
}

if (! function_exists('setting')) {
    /** setting('general.store_email') — for translatable settings use settings()->trans(). */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('store_route')) {
    /** Storefront route in the visitor's current language: store_route('login'). */
    function store_route(string $name, mixed $parameters = [], ?string $locale = null): string
    {
        return app(Localization::class)->route($name, $parameters, $locale);
    }
}

if (! function_exists('store_url')) {
    function store_url(string $path = '/', ?string $locale = null): string
    {
        return app(Localization::class)->url($path, $locale);
    }
}

if (! function_exists('price')) {
    /** Show a base-currency (AED) amount in the visitor's currency: price($product->price). */
    function price(Money|string|int|null $amount): string
    {
        if ($amount === null) {
            return '';
        }

        if (! $amount instanceof Money) {
            $amount = Money::of($amount, config('store.base_currency'));
        }

        return app(CurrencyService::class)->display($amount);
    }
}

if (! function_exists('price_whole')) {
    /**
     * A price the way the Furnivo design writes it: symbol then the whole amount
     * ("$24,500"), with cents only when there are any.
     */
    function price_whole(Money|string|int|null $amount): string
    {
        if ($amount === null) {
            return '';
        }

        if (! $amount instanceof Money) {
            $amount = Money::of($amount, config('store.base_currency'));
        }

        $currencies = app(CurrencyService::class);
        $shown = $currencies->convert($amount);
        $decimal = $shown->toDecimal();

        return ($currencies->current()->symbol ?: $shown->currency)
            .(str_ends_with($decimal, '.00') ? number_format((float) $decimal) : number_format((float) $decimal, 2));
    }
}

if (! function_exists('price_admin')) {
    /** Base-currency amount (or range) for admin screens: "AED 120.00" or "AED 120.00 – AED 150.00". */
    function price_admin(Money $min, ?Money $max = null): string
    {
        $currencies = app(CurrencyService::class);

        if ($max === null || $max->equals($min)) {
            return $currencies->format($min);
        }

        return $currencies->format($min).' – '.$currencies->format($max);
    }
}

if (! function_exists('price_order')) {
    /** An amount exactly as charged on an order (base currency, no conversion). */
    function price_order(Money $amount, ?\App\Models\Order $order = null): string
    {
        return app(CurrencyService::class)->format($amount);
    }
}
