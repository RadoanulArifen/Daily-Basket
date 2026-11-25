<?php

namespace App\Http\Requests\Storefront\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? preg_replace('/[^\d+]/', '', (string) $this->input('phone')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->withoutTrashed()],
            'phone' => ['nullable', 'string', 'regex:/^\+?\d{7,15}$/'],
            'password' => \App\Support\PasswordRules::confirmed(),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('store.auth.full_name'),
            'phone' => __('store.auth.phone'),
        ];
    }
}
