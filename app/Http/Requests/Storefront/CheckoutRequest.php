<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(\App\Support\AddressInput::normalize($this->all()));
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? preg_replace('/[^\d+]/', '', (string) $this->input('phone')) : null,
            'marketing_opt_in' => $this->boolean('marketing_opt_in'),
            'save_address' => $this->boolean('save_address', true),
        ]);
    }

    public function rules(): array
    {
        $settings = settings();

        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => [$settings->bool('checkout.require_phone', true) ? 'required' : 'nullable', 'string', 'regex:/^\+?\d{7,15}$/'],
            'name' => ['required', 'string', 'max:120'],
            ...\App\Support\AddressInput::rules(),
            'address_line' => ['required', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
            'shipping_method' => ['required', 'string', 'max:40'],
            'payment_method' => ['required', 'string', 'max:30'],
            'notes' => $settings->bool('checkout.order_notes', true) ? ['nullable', 'string', 'max:500'] : ['prohibited'],
            'marketing_opt_in' => ['boolean'],
            'save_address' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('store.auth.email'),
            'phone' => __('store.auth.phone'),
            'name' => __('store.checkout.full_name'),
            'country' => __('store.checkout.country'),
            'state' => __('store.checkout.state'),
            'city' => __('store.checkout.city'),
            'address_line' => __('store.checkout.address_line'),
            'building' => __('store.checkout.building'),
            'shipping_method' => __('store.checkout.delivery'),
            'payment_method' => __('store.checkout.payment'),
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('store.checkout.phone_invalid')];
    }
}
