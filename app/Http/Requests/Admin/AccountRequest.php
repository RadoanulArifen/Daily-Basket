<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique(Admin::class)->ignore($this->user('admin'))],
            'locale' => ['required', Rule::in(array_keys(config('store.locales')))],
        ];
    }
}
