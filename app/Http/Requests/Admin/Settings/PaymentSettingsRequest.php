<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cod_enabled' => $this->boolean('cod_enabled'),
            'stripe_enabled' => $this->boolean('stripe_enabled'),
            'cod_fee' => $this->filled('cod_fee') ? $this->input('cod_fee') : '0.00',
        ]);
    }

    public function rules(): array
    {
        return [
            'cod_enabled' => ['boolean'],
            'cod_fee' => ['numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'stripe_enabled' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->boolean('stripe_enabled') && blank(config('services.stripe.secret'))) {
                $validator->errors()->add('stripe_enabled', __('admin.settings.payments.stripe_needs_keys'));
            }
            if (! $this->boolean('stripe_enabled') && ! $this->boolean('cod_enabled')) {
                $validator->errors()->add('cod_enabled', __('admin.settings.payments.need_one'));
            }
        }];
    }
}
