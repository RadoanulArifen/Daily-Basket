<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Services\Currency\CurrencyService;
use Illuminate\Database\Seeder;

/**
 * AED is the base currency. The other rates are examples:
 * update them in Admin → Settings → Currencies before going live.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'AED', 'name' => ['en' => 'UAE Dirham', 'ar' => 'درهم إماراتي'], 'symbol' => 'AED', 'symbol_ar' => 'د.إ', 'exchange_rate' => '1', 'is_active' => true, 'sort_order' => 0, 'is_base' => true],
            ['code' => 'USD', 'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'], 'symbol' => 'USD', 'symbol_ar' => 'دولار', 'exchange_rate' => '0.27229000', 'is_active' => true, 'sort_order' => 1],
            ['code' => 'EUR', 'name' => ['en' => 'Euro', 'ar' => 'يورو'], 'symbol' => 'EUR', 'symbol_ar' => 'يورو', 'exchange_rate' => '0.23500000', 'is_active' => true, 'sort_order' => 2],
            ['code' => 'SAR', 'name' => ['en' => 'Saudi Riyal', 'ar' => 'ريال سعودي'], 'symbol' => 'SAR', 'symbol_ar' => 'ر.س', 'exchange_rate' => '1.02110000', 'is_active' => false, 'sort_order' => 3],
        ];

        foreach ($currencies as $data) {
            $isBase = $data['is_base'] ?? false;
            unset($data['is_base']);

            $currency = Currency::firstOrNew(['code' => $data['code']]);

            if (! $currency->exists) {
                $currency->fill($data);
                $currency->is_base = $isBase;
                $currency->save();
            }
        }

        app(CurrencyService::class)->flush();
    }
}
