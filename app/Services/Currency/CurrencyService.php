<?php

namespace App\Services\Currency;

use App\Models\Currency;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Currencies shown on the storefront.
 *
 * Prices are always stored in the base currency (AED). The visitor can pick
 * another active currency; amounts are converted with the admin-set rate.
 */
class CurrencyService
{
    private const CACHE_KEY = 'store.currencies';

    public const SESSION_KEY = 'currency';

    private ?Collection $currencies = null;

    /** @return Collection<string, Currency> keyed by code, active only */
    public function active(): Collection
    {
        return $this->all()->filter->is_active;
    }

    /** @return Collection<string, Currency> keyed by code */
    public function all(): Collection
    {
        // Cached as plain arrays (Laravel refuses to unserialize objects from cache),
        // then turned back into models.
        return $this->currencies ??= Currency::hydrate(Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Currency::query()->ordered()->get()->map->getAttributes()->all(),
        ))->keyBy('code');
    }

    public function baseCode(): string
    {
        return config('store.base_currency');
    }

    public function base(): Currency
    {
        return $this->all()->get($this->baseCode())
            ?? new Currency(['code' => $this->baseCode(), 'symbol' => $this->baseCode(), 'decimal_places' => 2, 'exchange_rate' => 1]);
    }

    /** The currency the visitor is browsing in. */
    public function current(): Currency
    {
        $code = session(self::SESSION_KEY);

        return $this->active()->get($code) ?? $this->base();
    }

    public function isAvailable(?string $code): bool
    {
        return $code !== null && $this->active()->has(strtoupper($code));
    }

    public function setCurrent(string $code): void
    {
        session([self::SESSION_KEY => strtoupper($code)]);
    }

    /** Convert a base-currency amount to the given (or current) currency. */
    public function convert(Money $amount, ?Currency $to = null): Money
    {
        $to ??= $this->current();

        if ($amount->currency === $to->code) {
            return $amount;
        }

        return $amount->convert($to->code, (string) $to->exchange_rate, $to->decimal_places);
    }

    /** "AED 1,250.00" in English, "1,250.00 د.إ" in Arabic. */
    public function format(Money $amount, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $currency = $this->all()->get($amount->currency);
        $symbol = $currency?->symbolFor($locale) ?? $amount->currency;

        $number = number_format(abs($amount->minor) / (10 ** $amount->decimals), $amount->decimals, '.', ',');
        $sign = $amount->isNegative() ? '-' : '';

        return $locale === 'ar'
            ? $sign.$number."\u{00A0}".$symbol
            : $sign.$symbol."\u{00A0}".$number;
    }

    /** Convert from base currency and format in one step. */
    public function display(Money $baseAmount): string
    {
        return $this->format($this->convert($baseAmount));
    }

    public function flush(): void
    {
        $this->currencies = null;
        Cache::forget(self::CACHE_KEY);
    }
}
