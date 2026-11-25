@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin_traffic.title'))

{{--
    Analytics → Traffic. Visits are recorded by App\Http\Middleware\RecordTraffic (no IP addresses kept).
    $report: App\Services\Traffic\TrafficReport::for($period)
--}}
@section('content')
    @php
        $s = $report['stats'];
        $num = fn ($n) => number_format((int) $n);
        $pct = fn ($n) => $n === null ? null : (($n >= 0 ? '+' : '').$n.'%');
    @endphp

    <x-admin.page-header :title="__('admin_traffic.title')" :back="route('admin.analytics')" :subtitle="__('admin_traffic.subtitle')">
        <x-slot:actions>
            <div class="inline-flex flex-wrap rounded-lg border border-line-strong bg-surface p-0.5">
                @foreach (\App\Services\Traffic\TrafficReport::PERIODS as $key)
                    <a href="{{ route('admin.traffic', ['period' => $key]) }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $report['period'] === $key, 'text-ink-soft hover:text-ink' => $report['period'] !== $key])
                       @if ($report['period'] === $key) aria-current="page" @endif>{{ __("admin_traffic.periods.{$key}") }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if (! $report['geo_ready'])
        <x-admin.alert tone="warning" class="mb-4" :title="__('admin_traffic.geo_missing_title')">
            {{ __('admin_traffic.geo_missing') }} <code dir="ltr" class="font-mono">php artisan store:geoip-update</code>
        </x-admin.alert>
    @endif

    {{-- Six boxes --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 shadow-xs">
            <span class="flex items-center gap-2 text-[13px] text-success-700">
                <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-success-500 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-success-600"></span></span>
                {{ __('admin_traffic.kpi.live') }}
            </span>
            <span class="mt-1 block text-2xl font-semibold text-success-700" data-live-count>{{ $num($s['live']) }}</span>
            <span class="mt-1 block text-xs text-success-700">{{ __('admin_traffic.kpi.live_help') }}</span>
        </div>
        @foreach ([
            ['visitors', $num($s['visitors']), $s['visitors_change'] !== null ? __('admin_traffic.vs_previous', ['change' => $pct($s['visitors_change'])]) : __('admin_traffic.no_previous'), $s['visitors_change'] !== null && $s['visitors_change'] < 0],
            ['visits', $num($s['visits']), __('admin_traffic.kpi.new_share', ['share' => $s['new_share']]), false],
            ['pages', $num($s['pages']), __('admin_traffic.kpi.per_visit', ['n' => $s['pages_per_visit']]), false],
            ['countries', $num($s['countries']), $s['top_country'] ? __('admin_traffic.kpi.top_country', ['country' => $s['top_country']['name'], 'share' => $s['top_country']['share']]) : '—', false],
        ] as [$key, $value, $sub, $down])
            <div class="rounded-xl border border-line bg-surface p-4 shadow-xs">
                <span class="block text-[13px] text-muted">{{ __("admin_traffic.kpi.{$key}") }}</span>
                <span class="mt-1 block text-2xl font-semibold">{{ $value }}</span>
                <span @class(['mt-1 block text-xs', 'text-danger-600' => $down, 'text-muted' => ! $down])>{{ $sub }}</span>
            </div>
        @endforeach
        <div class="rounded-xl border border-line bg-surface p-4 shadow-xs">
            <span class="block text-[13px] text-muted">{{ __('admin_traffic.kpi.bots') }}</span>
            <span class="mt-1 block text-2xl font-semibold">{{ $num($s['bots']['total']) }}</span>
            <span class="mt-1 block text-xs text-muted">{{ __('admin_traffic.kpi.bots_split', ['search' => $num($s['bots']['search']), 'ai' => $num($s['bots']['ai']), 'fake' => $num($s['bots']['fake']), 'other' => $num($s['bots']['other'])]) }}</span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- World map: shaded by visitors; green pins are people on the shop now. --}}
        <x-admin.card :title="__('admin_traffic.map_title')" class="min-w-0">
            <div x-data="trafficMap" data-countries='@json($report['map'])' data-live='@json($report['live'])' data-labels='@json(__('admin_traffic.tooltip'))'
                 class="relative" dir="ltr">
                <div class="traffic-map" data-map>@include('admin.insights.partials.world-map')</div>
                <div x-show="tip" x-cloak class="pointer-events-none absolute z-10 rounded-lg bg-ink-strong px-3 py-2 text-xs text-white shadow-popover" x-bind:style="tipStyle">
                    <span class="block font-semibold" x-text="tip?.name"></span>
                    <span class="block" x-text="tip?.line"></span>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted">
                    <span class="inline-flex items-center gap-2">{{ __('admin_traffic.legend_fewer') }}
                        <span class="inline-flex overflow-hidden rounded">@foreach (\App\Services\Traffic\TrafficReport::SHADES as $shade)<span class="block h-2.5 w-6" style="background: {{ $shade }}"></span>@endforeach</span>
                        {{ __('admin_traffic.legend_more') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-success-600 ring-2 ring-success-200"></span>{{ __('admin_traffic.legend_live') }}</span>
                </div>
            </div>
        </x-admin.card>

        {{-- Live now --}}
        <div x-data="trafficLive" data-url="{{ route('admin.traffic.live') }}" class="min-w-0">
            <x-admin.card :title="__('admin_traffic.live_title')" :padding="false">
                <x-slot:actions>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-muted">
                        <span x-text="on ? @js(__('admin_traffic.live_on')) : @js(__('admin_traffic.live_off'))">{{ __('admin_traffic.live_on') }}</span>
                        <input type="checkbox" class="peer sr-only" x-model="on" checked>
                        <span class="relative h-5 w-9 rounded-full bg-[#c9c9c9] transition peer-checked:bg-success-600 after:absolute after:top-0.5 after:start-0.5 after:size-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-4 rtl:peer-checked:after:-translate-x-4"></span>
                    </label>
                </x-slot:actions>
                <ul class="max-h-[26rem] divide-y divide-line overflow-y-auto" data-live-list>
                    @forelse ($report['live'] as $row)
                        <li class="flex items-start gap-3 px-4 py-2.5">
                            <span class="mt-0.5 text-lg leading-none" aria-hidden="true">{{ $row['flag'] }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-medium text-ink-strong">{{ $row['place'] }}</span>
                                <span class="block truncate font-mono text-xs text-muted" dir="ltr">{{ $row['path'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-muted">{{ $row['ago'] }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-[13px] text-muted">{{ __('admin_traffic.live_empty') }}</li>
                    @endforelse
                </ul>
                <p class="border-t border-line px-4 py-2 text-xs text-muted">{{ __('admin_traffic.live_help') }}</p>
            </x-admin.card>
        </div>
    </div>

    {{-- Top countries --}}
    <x-admin.card :title="__('admin_traffic.countries_title')" :padding="false" class="mt-6">
        @if ($report['countries'] === [])
            <p class="px-4 py-10 text-center text-sm text-muted">{{ __('admin_traffic.no_visits') }}</p>
        @else
            <table class="w-full text-[13px]">
                <thead class="text-xs text-muted">
                    <tr class="border-b border-line">
                        <th class="px-4 py-2 text-start font-medium">{{ __('admin_traffic.col_country') }}</th>
                        <th class="px-4 py-2 text-end font-medium">{{ __('admin_traffic.col_visitors') }}</th>
                        <th class="hidden px-4 py-2 text-end font-medium sm:table-cell">{{ __('admin_traffic.col_visits') }}</th>
                        <th class="hidden px-4 py-2 text-end font-medium sm:table-cell">{{ __('admin_traffic.col_pages') }}</th>
                        <th class="hidden w-1/4 px-4 py-2 text-start font-medium md:table-cell">{{ __('admin_traffic.col_share') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_slice($report['countries'], 0, 12) as $c)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-2.5"><span class="me-2" aria-hidden="true">{{ $c['flag'] }}</span>{{ $c['name'] }}</td>
                            <td class="px-4 py-2.5 text-end font-medium">{{ $num($c['visitors']) }}</td>
                            <td class="hidden px-4 py-2.5 text-end sm:table-cell">{{ $num($c['visits']) }}</td>
                            <td class="hidden px-4 py-2.5 text-end sm:table-cell">{{ $num($c['pages']) }}</td>
                            <td class="hidden px-4 py-2.5 md:table-cell">
                                <span class="flex items-center gap-2"><span class="h-1.5 flex-1 overflow-hidden rounded-full bg-subdued"><span class="block h-full rounded-full bg-brand-600" style="width: {{ $c['share'] }}%"></span></span><span class="w-9 text-end text-xs text-muted">{{ $c['share'] }}%</span></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-admin.card>

    <p class="mt-4 text-xs text-muted">
        {{ __('admin_traffic.privacy') }}
        <a href="https://db-ip.com" target="_blank" rel="noopener" class="underline hover:text-ink">{{ __('admin_traffic.credit') }}</a>
    </p>
@endsection
