<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Email/password login with brute-force protection (5 attempts per email + IP).
 * Used by both the storefront and the admin login requests.
 */
trait AuthenticatesWithThrottle
{
    /** Failed sign-ins allowed per email + IP before a lockout. */
    private const MAX_ATTEMPTS = 5;

    /** How long the lockout lasts (seconds). */
    private const LOCKOUT_SECONDS = 300;

    abstract protected function guardName(): string;

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::guard($this->guardName())->attempt($credentials + $this->extraCredentials(), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** Extra columns that must match, e.g. ['is_active' => true]. */
    protected function extraCredentials(): array
    {
        return [];
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        // A hash, not a transliteration: two different addresses can never collide onto
        // one another's rate limit, whatever alphabet they are written in.
        return hash('sha256', Str::lower((string) $this->string('email'))).'|'.$this->ip();
    }
}
