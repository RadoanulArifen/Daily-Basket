<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts a DECIMAL column to App\Support\Money.
 *
 *   'price' => MoneyCast::class                  // base currency (AED)
 *   'total' => MoneyCast::class.':currency_code' // currency read from another column
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(private ?string $currencyColumn = null) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of((string) $value, $this->currency($attributes));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->toDecimal();
        }

        if (is_numeric($value)) {
            return Money::of((string) $value, $this->currency($attributes))->toDecimal();
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be a Money instance or a numeric value.");
    }

    private function currency(array $attributes): string
    {
        if ($this->currencyColumn && ! empty($attributes[$this->currencyColumn])) {
            return $attributes[$this->currencyColumn];
        }

        return config('store.base_currency');
    }
}
