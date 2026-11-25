<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'prices_include_tax' => $this->boolean('prices_include_tax'),
            'show_on_invoice' => $this->boolean('show_on_invoice'),
        ]);
    }

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'rate' => ['required_if:enabled,true', 'nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'label' => ['required', 'array'],
            'label.en' => ['required', 'string', 'max:40'],
            'label.ar' => ['nullable', 'string', 'max:40'],
            'prices_include_tax' => ['boolean'],
            'show_on_invoice' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rate' => __('admin.settings.tax.rate'),
            'label.en' => __('admin.settings.tax.label').' (English)',
        ];
    }
}
