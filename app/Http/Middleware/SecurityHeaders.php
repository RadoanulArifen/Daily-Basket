<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for every page, including a Content-Security-Policy.
 *
 * About the CSP: scripts may only come from this site (plus Google Analytics when it's
 * switched on). 'unsafe-eval' is required because Alpine.js (bundled into our own scripts)
 * evaluates the small expressions written in x-data / @click attributes. The few inline
 * <script> blocks carry a per-request nonce. Inline on…="" handlers are not allowed —
 * use Alpine (@change, @click) instead.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Must run before views render, so @vite tags and our inline scripts get the nonce.
        $nonce = Vite::useCspNonce();

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');

        // X-Frame-Options can only say "same site"; when other sites may frame us (ALLOW_FRAMING_FROM)
        // it is left out and the CSP frame-ancestors list below decides. The admin always sends DENY.
        if (! $headers->has('X-Frame-Options') && ! \App\Support\FrameAncestors::allowsOthers()) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if ($this->isHtml($response) && ! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->policy($nonce, $headers->get('X-Frame-Options') === 'DENY'));
        }

        // Tell browsers to only ever use HTTPS for this site (production + HTTPS only).
        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce, bool $noFraming): string
    {
        $script = ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'"];
        $style = ["'self'", "'unsafe-inline'"];            // Tailwind/Alpine set inline styles (widths, x-show)
        $connect = ["'self'"];
        $img = ["'self'", 'data:', 'blob:'];

        if ($this->analyticsEnabled()) {
            $script[] = 'https://www.googletagmanager.com';
            $connect = [...$connect, 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com'];
            $img = [...$img, 'https://*.google-analytics.com', 'https://*.googletagmanager.com'];
        }

        // reCAPTCHA loads its script and an invisible frame from Google.
        $recaptcha = false;
        if ($recaptcha = app(\App\Services\Security\Recaptcha::class)->enabled()) {
            $script = [...$script, 'https://www.google.com', 'https://www.gstatic.com'];
            $connect = [...$connect, 'https://www.google.com'];
        }

        // Online Store → Custom code: domains the owner trusts for pixels, chat widgets etc.
        $trusted = $this->trustedDomains();
        if ($trusted) {
            $script = [...$script, ...$trusted];
            $connect = [...$connect, ...$trusted];
            $img = [...$img, ...$trusted];
        }

        // `npm run dev` serves assets from the Vite dev server.
        if (Vite::isRunningHot()) {
            $hot = rtrim(trim((string) @file_get_contents(public_path('hot'))), '/');
            $ws = preg_replace('#^http#', 'ws', $hot);
            $script[] = $hot;
            $style[] = $hot;
            $connect = [...$connect, $hot, $ws];
            $img[] = $hot;
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            'img-src '.implode(' ', $img),
            "font-src 'self' data:",
            // Videos added in the HTML editor: YouTube (no-cookie) and Vimeo players only (App\Support\RichText::EMBEDS).
            "media-src 'self' https:",
            'frame-src '.implode(' ', ["'self'", 'https://checkout.stripe.com', 'https://www.youtube-nocookie.com', 'https://www.youtube.com', 'https://player.vimeo.com', ...($recaptcha ? ['https://www.google.com'] : []), ...$trusted]),
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri 'self'",
            // Checkout posts here and is then redirected to Stripe's payment page.
            "form-action 'self' https://checkout.stripe.com",
            'frame-ancestors '.($noFraming ? "'none'" : \App\Support\FrameAncestors::directive()),
        ]);
    }

    private function analyticsEnabled(): bool
    {
        try {
            return filled(settings()->get('tracking.ga_measurement_id'));
        } catch (\Throwable) {
            return false; // e.g. first install, before the settings table exists
        }
    }

    /** @return list<string> */
    private function trustedDomains(): array
    {
        try {
            return app(\App\Services\Theme\CustomCode::class)->get()['domains'];
        } catch (\Throwable) {
            return [];
        }
    }

    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
