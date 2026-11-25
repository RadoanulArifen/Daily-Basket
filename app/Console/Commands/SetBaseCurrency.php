<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Services\Currency\BaseCurrencySwitch;
use Illuminate\Console\Command;

/**
 * Change the currency prices are kept in, from the command line.
 *
 * The same job as "Keep prices in this" in the admin, for when you would rather do it over
 * SSH — or check what would happen first with --pretend.
 */
class SetBaseCurrency extends Command
{
    protected $signature = 'store:base-currency {code : The currency code, e.g. USD} {--pretend : Show what would change and stop}';

    protected $description = 'Make a currency the one prices are stored in';

    public function handle(BaseCurrencySwitch $switch): int
    {
        $code = strtoupper((string) $this->argument('code'));
        $target = Currency::query()->where('code', strtoupper((string) $code))->first();

        if (! $target) {
            $this->components->error("No currency with the code {$code}. Add it in Settings → Currencies first.");

            return self::FAILURE;
        }

        if ($target->is_base) {
            $this->components->info("Prices are already kept in {$code}.");

            return self::SUCCESS;
        }

        $rate = (float) $target->exchange_rate;

        if ($rate <= 0) {
            $this->components->error("{$code} has no exchange rate set, so prices cannot be converted.");

            return self::FAILURE;
        }

        $sample = \App\Models\ProductVariant::query()->whereNotNull('price')->first();
        $before = $sample ? (int) $sample->getRawOriginal('price') : null;

        $this->components->info("Every catalogue price will be multiplied by {$rate}.");

        if ($before !== null) {
            $this->components->info('For example: '.number_format($before / 100, 2).' becomes '.number_format(round($before * $rate) / 100, 2).'.');
        }

        $this->components->warn('Past orders, payments and refunds are left exactly as they are.');

        if ($this->option('pretend')) {
            $this->components->info('Nothing changed (--pretend).');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! $this->confirm('Go ahead?', false)) {
            return self::SUCCESS;
        }

        $switch->to($target);

        $this->components->info("Prices are now kept in {$code}. Open a few products and check them.");

        return self::SUCCESS;
    }
}
