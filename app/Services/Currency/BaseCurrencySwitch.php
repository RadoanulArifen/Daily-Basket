<?php

namespace App\Services\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Changing which currency the shop keeps its prices in.
 *
 * Catalogue prices are stored as integers in the base currency. Making another currency the
 * base therefore means converting those integers at its rate, and re-basing every other
 * currency's rate against the new one.
 *
 * What is converted: product and variant prices, discount thresholds and amounts, and
 * delivery rates — the numbers that describe what you charge today.
 *
 * What is not: orders, payments and refunds. Those record what was actually charged, in the
 * currency it was charged in. Rewriting them would falsify your accounts.
 */
class BaseCurrencySwitch
{
    /** table => money columns stored in the base currency. */
    private const PRICE_COLUMNS = [
        'product_variants' => ['price', 'compare_at_price', 'cost_price'],
        'products' => ['from_price'],
        'discounts' => ['min_subtotal', 'amount'],
        'shipping_rates' => ['price', 'min_subtotal', 'free_over'],
    ];

    public function to(Currency $target): void
    {
        $rate = (float) $target->exchange_rate;

        if ($rate <= 0) {
            throw new RuntimeException('That currency has no exchange rate set.');
        }

        DB::transaction(function () use ($target, $rate) {
            foreach (self::PRICE_COLUMNS as $table => $columns) {
                if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                    continue;
                }

                foreach ($columns as $column) {
                    if (! \Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    // Integers in minor units, rounded to the nearest unit.
                    // Identifiers come from the fixed PRICE_COLUMNS list and are quoted by the grammar;
                    // the rate is passed as a bound parameter (no string-built values).
                    $grammar = DB::getQueryGrammar();
                    $col = $grammar->wrap($column);
                    DB::update('update '.$grammar->wrapTable($table).' set '.$col.' = round('.$col.' * ?) where '.$col.' is not null', [(float) $rate]);
                }
            }

            // Every other currency's rate is now relative to the new base.
            foreach (Currency::query()->get() as $other) {
                $other->forceFill([
                    'is_base' => $other->id === $target->id,
                    'exchange_rate' => $other->id === $target->id ? 1 : round((float) $other->exchange_rate / $rate, 8),
                    'is_active' => $other->id === $target->id ? true : $other->is_active,
                ])->save();
            }
        });
    }
}
