<?php

namespace App\Http\Requests\Admin\Auth;

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
        return 'admin';
    }

    /** Deactivated admins can't sign in. */
    protected function extraCredentials(): array
    {
        return ['is_active' => true];
    }
}
