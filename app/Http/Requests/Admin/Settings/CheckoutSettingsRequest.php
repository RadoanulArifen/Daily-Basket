<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSettingsRequest extends FormRequest
{
    public const TOGGLES = ['guest_checkout', 'require_phone', 'order_notes', 'marketing_opt_in'];

    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(self::TOGGLES)->mapWithKeys(fn ($key) => [$key => $this->boolean($key)])->all());
    }

    public function rules(): array
    {
        return collect(self::TOGGLES)->mapWithKeys(fn ($key) => [$key => ['boolean']])->all();
    }
}
