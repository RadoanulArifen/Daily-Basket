<?php

namespace App\Rules;

use App\Services\Security\Recaptcha as RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validation rule for the reCAPTCHA token: ['g-recaptcha-response' => [new Recaptcha]]. */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(RecaptchaService::class)->verify(is_string($value) ? $value : null, request()->ip())) {
            $fail(__('store.forms.recaptcha_failed'));
        }
    }
}
