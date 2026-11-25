<?php

namespace App\Themes\Freshnest;

use App\Services\Currency\CurrencyService;
use App\Support\Money;

/**
 * Prices as the FreshNest design writes them: "$4.20", with the symbol against the number.
 * Converted to the visitor's currency like everywhere else; a letter code ("AED") keeps
 * its space.
 */
class Price
{
    public static function show(Money $amount): string
    {
        $currencies = app(CurrencyService::class);
        $text = $currencies->display($amount);

        return preg_replace('/^(-?[^\p{L}\d\s]+)\x{00A0}/u', '$1', $text);
    }
}
