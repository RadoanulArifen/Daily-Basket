<?php

namespace App\Http\Requests\Storefront\Auth;

use App\Http\Requests\Concerns\AuthenticatesWithThrottle;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use AuthenticatesWithThrottle;

    public function authorize(): bool
    {
        return true;
    }

    protected function guardName(): string
    {
        return 'web';
    }

    /** Deactivated customers cannot log in. */
    protected function extraCredentials(): array
    {
        return ['is_active' => true];
    }
}
