<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'flat_enabled' => $this->boolean('flat_enabled'),
            'free_enabled' => $this->boolean('free_enabled'),
        ]);
    }

    public function rules(): array
    {
        $money = ['numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'];

        return [
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Countries::NAMES))],
            'default_country' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Countries::NAMES))],
            'flat_enabled' => ['boolean'],
            'flat_name.en' => ['required', 'string', 'max:60'],
            'flat_name.ar' => ['nullable', 'string', 'max:60'],
            'flat_description.en' => ['nullable', 'string', 'max:120'],
            'flat_description.ar' => ['nullable', 'string', 'max:120'],
            'flat_rate' => ['required', ...$money],
            'free_enabled' => ['boolean'],
            'free_threshold' => ['required_if:free_enabled,true', 'nullable', ...$money],
        ];
    }

    public function attributes(): array
    {
        return [
            'flat_name.en' => __('admin.settings.shipping.flat_name').' (English)',
            'flat_rate' => __('admin.settings.shipping.flat_rate'),
            'free_threshold' => __('admin.settings.shipping.free_threshold'),
        ];
    }
}
