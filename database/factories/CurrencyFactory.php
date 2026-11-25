<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => 'USD',
            'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
            'symbol' => '$',
            'symbol_ar' => '$',
            'decimal_places' => 2,
            'exchange_rate' => '0.27229000',
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function base(): static
    {
        return $this->state(fn () => [
            'code' => 'AED',
            'name' => ['en' => 'UAE Dirham', 'ar' => 'درهم إماراتي'],
            'symbol' => 'AED',
            'symbol_ar' => 'د.إ',
            'exchange_rate' => '1',
            'sort_order' => 0,
        ])->afterMaking(fn (Currency $currency) => $currency->is_base = true);
    }
}
