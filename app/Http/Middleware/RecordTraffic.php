<?php

namespace App\Http\Middleware;

use App\Services\Traffic\BotDetector;
use App\Support\Geo\GeoIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts storefront visits for Analytics → Traffic, after the page has been sent.
 *
 * Counted: GET requests that return a 200 HTML page on a storefront route. Not counted:
 * AJAX, prefetches, signed-in admins, theme previews. Bots are only tallied per day and kind.
 * No IP address is stored: a visitor is the first 16 characters of an HMAC of IP and user
 * agent with APP_KEY, and only the page path is kept (never the query string).
 * A visit ends after 30 quiet minutes. Nothing here can break a page.
 */
class RecordTraffic
{
    public const VISIT_GAP_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if ($this->countable($request, $response)) {
                $this->record($request);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function countable(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }
        if ($request->ajax() || $request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return false;
        }
        $purpose = strtolower($request->header('Purpose', '').' '.$request->header('Sec-Purpose', '').' '.$request->header('X-Moz', ''));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }
        // Storefront pages only (not the admin, the landing page or the product tour).
        $middleware = $request->route()?->gatherMiddleware() ?? [];
        if (! collect($middleware)->contains(fn ($m) => is_string($m) && str_starts_with($m, 'storefront.locale'))) {
            return false;
        }
        if (auth('admin')->check() || ($request->hasSession() && $request->session()->get('theme_preview'))) {
            return false;
        }

        return true;
    }

    private function record(Request $request): void
    {
        $now = now();
        $kind = BotDetector::kind($request->userAgent(), $request->header('Accept-Language'));

        if ($kind !== null) {
            $day = $now->toDateString();
            $updated = DB::table('traffic_bot_counts')->where('day', $day)->where('kind', $kind)->increment('hits');
            if (! $updated) {
                DB::table('traffic_bot_counts')->insertOrIgnore(['day' => $day, 'kind' => $kind, 'hits' => 1]);
            }

            return;
        }

        $visitor = substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), (string) config('app.key')), 0, 16);
        $path = '/'.ltrim(mb_substr((string) parse_url($request->getRequestUri(), PHP_URL_PATH), 0, 250), '/');

        $open = DB::table('traffic_visits')
            ->where('visitor', $visitor)
            ->where('last_seen_at', '>=', $now->copy()->subMinutes(self::VISIT_GAP_MINUTES))
            ->orderByDesc('id')
            ->first(['id']);

        if ($open) {
            DB::table('traffic_visits')->where('id', $open->id)->update([
                'pages' => DB::raw('pages + 1'),
                'path' => $path,
                'last_seen_at' => $now,
            ]);

            return;
        }

        $place = app(GeoIp::class)->lookup($request->ip());
        DB::table('traffic_visits')->insert([
            'visitor' => $visitor,
            'country' => $place['country'],
            'city' => $place['city'],
            'lat' => $place['lat'],
            'lon' => $place['lon'],
            'is_new' => ! DB::table('traffic_visits')->where('visitor', $visitor)->exists(),
            'pages' => 1,
            'path' => $path,
            'started_at' => $now,
            'last_seen_at' => $now,
        ]);
    }
}
