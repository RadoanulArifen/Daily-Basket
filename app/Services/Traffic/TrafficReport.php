<?php

namespace App\Services\Traffic;

use App\Support\Geo\GeoIp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Numbers for Analytics → Traffic. All times are stored in UTC; periods follow the store's time zone. */
class TrafficReport
{
    public const PERIODS = ['today', '7d', '30d', '90d', '12m'];
    public const SHADES = ['#e8f1fc', '#c6dcf7', '#94bdf0', '#5b97e3', '#2f74d0', '#0a5fd6'];
    public const LIVE_MINUTES = 5;

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: CarbonImmutable} start, end, previous start (UTC) */
    public static function range(string $period, ?CarbonImmutable $now = null): array
    {
        $tz = config('store.display_timezone', 'UTC');
        $now ??= CarbonImmutable::now($tz);
        $now = $now->setTimezone($tz);

        $start = match ($period) {
            'today' => $now->startOfDay(),
            '7d' => $now->subDays(6)->startOfDay(),
            '90d' => $now->subDays(89)->startOfDay(),
            '12m' => $now->subMonths(12)->addDay()->startOfDay(),
            default => $now->subDays(29)->startOfDay(),
        };
        $previous = $start->sub($start->diffAsCarbonInterval($now));

        return [$start->utc(), $now->utc(), $previous->utc()];
    }

    /** @return array<string, mixed> */
    public static function for(string $period): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '30d';
        [$start, $end, $prevStart] = self::range($period);
        $inPeriod = fn () => DB::table('traffic_visits')->where('started_at', '>=', $start)->where('started_at', '<=', $end);

        $totals = $inPeriod()->selectRaw('COUNT(*) AS visits, COUNT(DISTINCT visitor) AS visitors, COALESCE(SUM(pages), 0) AS pages, COALESCE(SUM(CASE WHEN is_new THEN 1 ELSE 0 END), 0) AS new_visits')->first();
        $previousVisitors = (int) DB::table('traffic_visits')->where('started_at', '>=', $prevStart)->where('started_at', '<', $start)->distinct()->count('visitor');

        $rows = $inPeriod()->whereNotNull('country')
            ->selectRaw('country, COUNT(DISTINCT visitor) AS visitors, COUNT(*) AS visits, SUM(pages) AS pages')
            ->groupBy('country')->orderByDesc('visitors')->get();
        $located = max(1, (int) $rows->sum('visitors'));
        $countries = $rows->map(fn ($r) => [
            'cc' => $r->country,
            'name' => self::countryName($r->country),
            'flag' => self::flag($r->country),
            'visitors' => (int) $r->visitors,
            'visits' => (int) $r->visits,
            'pages' => (int) $r->pages,
            'share' => round($r->visitors * 100 / $located, 1),
        ])->values()->all();

        $bots = DB::table('traffic_bot_counts')
            ->where('day', '>=', $start->setTimezone(config('store.display_timezone', 'UTC'))->toDateString())
            ->where('day', '<=', $end->setTimezone(config('store.display_timezone', 'UTC'))->toDateString())
            ->selectRaw('kind, SUM(hits) AS hits')->groupBy('kind')->pluck('hits', 'kind');
        $bots = collect(BotDetector::KINDS)->mapWithKeys(fn ($k) => [$k => (int) ($bots[$k] ?? 0)]);

        $visits = (int) $totals->visits;
        $visitors = (int) $totals->visitors;
        $live = self::live();

        return [
            'period' => $period,
            'geo_ready' => app(GeoIp::class)->ready(),
            'stats' => [
                'live' => $live['count'],
                'visitors' => $visitors,
                'visitors_change' => $previousVisitors > 0 ? (int) round(($visitors - $previousVisitors) / $previousVisitors * 100) : null,
                'visits' => $visits,
                'new_share' => $visits ? (int) round($totals->new_visits * 100 / $visits) : 0,
                'pages' => (int) $totals->pages,
                'pages_per_visit' => $visits ? round($totals->pages / $visits, 1) : 0,
                'countries' => count($countries),
                'top_country' => $countries ? ['name' => $countries[0]['name'], 'share' => (int) round($countries[0]['share'])] : null,
                'bots' => ['total' => $bots->sum()] + $bots->all(),
            ],
            'map' => collect($countries)->mapWithKeys(fn ($c) => [$c['cc'] => ['name' => $c['name'], 'visitors' => $c['visitors'], 'visits' => $c['visits'], 'pages' => $c['pages']]])->all(),
            'countries' => $countries,
            'live' => $live['rows'],
        ];
    }

    /** People active in the last few minutes, newest first. @return array{count: int, rows: list<array<string, mixed>>} */
    public static function live(): array
    {
        $since = now()->subMinutes(self::LIVE_MINUTES);
        $count = (int) DB::table('traffic_visits')->where('last_seen_at', '>=', $since)->distinct()->count('visitor');

        $rows = DB::table('traffic_visits')->where('last_seen_at', '>=', $since)
            ->orderByDesc('last_seen_at')->limit(30)
            ->get(['visitor', 'country', 'city', 'lat', 'lon', 'path', 'last_seen_at'])
            ->unique('visitor')
            ->map(function ($r) {
                $minutes = (int) floor(CarbonImmutable::parse($r->last_seen_at, 'UTC')->diffInSeconds(now(), true) / 60);
                $place = array_filter([$r->city, $r->country ? self::countryName($r->country) : null]);

                return [
                    'cc' => $r->country,
                    'flag' => $r->country ? self::flag($r->country) : '🌐',
                    'place' => $place ? implode(', ', $place) : __('admin_traffic.unknown_place'),
                    'path' => $r->path,
                    'ago' => $minutes < 1 ? __('admin_traffic.just_now') : trans_choice('admin_traffic.minutes_ago', $minutes, ['count' => $minutes]),
                    'lat' => $r->lat !== null ? (float) $r->lat : null,
                    'lon' => $r->lon !== null ? (float) $r->lon : null,
                ];
            })->values()->all();

        return ['count' => $count, 'rows' => $rows];
    }

    public static function countryName(string $cc): string
    {
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$cc, app()->getLocale()) : '';

        return $name !== '' && $name !== $cc ? $name : $cc;
    }

    public static function flag(string $cc): string
    {
        if (! preg_match('/^[A-Z]{2}$/', $cc)) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 + ord($cc[0]) - 65).mb_chr(0x1F1E6 + ord($cc[1]) - 65);
    }
}
