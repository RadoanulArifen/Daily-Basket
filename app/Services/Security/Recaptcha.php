<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA v3 on the store's public forms.
 *
 * It only does anything once both keys are set (in the admin or .env), so a store without
 * keys keeps working exactly as before.
 */
class Recaptcha
{
    public function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret'));
    }

    public function siteKey(): ?string
    {
        return config('services.recaptcha.site_key');
    }

    /** True when the token is real and scores above the threshold. */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(6)
                ->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $e) {
            // Google unreachable: let the person through rather than blocking real orders.
            Log::warning('reCAPTCHA could not be checked: '.$e->getMessage());

            return true;
        }

        $body = $response->json();

        if (! ($body['success'] ?? false)) {
            return false;
        }

        // v2 responses have no score; v3 does.
        return ! isset($body['score']) || (float) $body['score'] >= (float) config('services.recaptcha.min_score', 0.5);
    }
}
