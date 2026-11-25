<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
        ]);
    }

    public function rules(): array
    {
        /** @var Currency|null $currency */
        $currency = $this->route('currency');
        $isBase = $currency?->is_base ?? false;

        return [
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique(Currency::class)->ignore($currency)],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:60'],
            'name.ar' => ['nullable', 'string', 'max:60'],
            'symbol' => ['required', 'string', 'max:10'],
            'symbol_ar' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:3'],
            // The base currency is always 1.
            'exchange_rate' => $isBase ? ['prohibited'] : ['required', 'numeric', 'gt:0', 'max:9999999', 'decimal:0,8'],
            'is_active' => $isBase ? ['accepted'] : ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('admin.currencies.code'),
            'name.en' => __('admin.currencies.name').' (English)',
            'symbol' => __('admin.currencies.symbol'),
            'exchange_rate' => __('admin.currencies.rate'),
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.accepted' => __('admin.currencies.base_always_active'),
        ];
    }
}
