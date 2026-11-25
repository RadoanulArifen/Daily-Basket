@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.analytics.title'))

@section('content')
    @php
        $t = $report['totals'];
        $p = $report['previous'];
        $change = function (int $now, int $before) {
            if ($before === 0) { return null; }
            return (int) round(($now - $before) / $before * 100);
        };
        $series = $report['series'];
        $max = max(1, ...array_map(fn ($b) => $b['sales']->minor, $series));
        $w = 720; $h = 220; $n = count($series); $gap = $n > 60 ? 1 : 3;
        $barW = max(1, ($w - $gap * ($n - 1)) / max(1, $n));
        $labelEvery = (int) ceil($n / 8);
    @endphp

    <x-admin.page-header :title="__('admin.analytics.title')"
        :subtitle="__('admin.analytics.period', ['from' => $report['start']->translatedFormat('j M Y'), 'to' => $report['end']->translatedFormat('j M Y')])">
        <x-slot:actions>
            @if (Route::has('admin.traffic'))
                <x-admin.button :href="route('admin.traffic')" variant="secondary" size="sm" icon="globe">{{ __('admin_traffic.button') }}</x-admin.button>
            @endif
            @if (auth('admin')->user()->mayAccess('activity'))
                <x-admin.button :href="route('admin.activity')" variant="ghost" size="sm" icon="clipboard-list">{{ __('admin.activity.title') }}</x-admin.button>
            @endif
            <div class="inline-flex rounded-lg border border-line-strong bg-surface p-0.5">
                @foreach (\App\Services\Analytics\SalesReport::RANGES as $days)
                    <a href="{{ route('admin.analytics', ['days' => $days]) }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $report['days'] === $days, 'text-ink-soft hover:text-ink' => $report['days'] !== $days])
                       @if ($report['days'] === $days) aria-current="page" @endif>{{ __("admin.analytics.ranges.{$days}") }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['sales', price_admin($t['sales']), $change($t['sales']->minor, $p['sales']->minor)],
            ['orders', number_format($t['orders']), $change($t['orders'], $p['orders'])],
            ['average', price_admin($t['average']), $change($t['average']->minor, $p['average']->minor)],
            ['units', number_format($t['units']), $change($t['units'], $p['units'])],
        ] as [$key, $value, $delta])
            <div class="rounded-xl border border-line bg-surface p-4 shadow-xs">
                <span class="block text-[13px] text-muted">{{ __("admin.analytics.kpi.{$key}") }}</span>
                <span class="mt-1 block text-xl font-semibold" dir="ltr">{{ $value }}</span>
                @if ($delta !== null)
                    <span @class(['mt-1 block text-xs', 'text-success-600' => $delta >= 0, 'text-danger-600' => $delta < 0])>{{ __('admin.analytics.vs_previous', ['change' => ($delta >= 0 ? '+' : '').$delta.'%']) }}</span>
                @endif
            </div>
        @endforeach
    </div>

    <x-admin.card :title="__('admin.analytics.sales_over_time')">
        @if ($t['orders'] === 0)
            <p class="py-10 text-center text-sm text-muted">{{ __('admin.analytics.no_sales') }}</p>
        @else
            <div class="overflow-x-auto" dir="ltr">
                <svg viewBox="0 0 {{ $w }} {{ $h + 24 }}" class="h-auto w-full min-w-[480px]" role="img" aria-label="{{ __('admin.analytics.sales_over_time') }}">
                    @foreach ([0.25, 0.5, 0.75, 1] as $line)
                        <line x1="0" x2="{{ $w }}" y1="{{ $h - $h * $line }}" y2="{{ $h - $h * $line }}" stroke="currentColor" class="text-line" stroke-width="1" />
                    @endforeach
                    @foreach ($series as $i => $bucket)
                        @php $bh = $bucket['sales']->minor / $max * ($h - 8); $x = $i * ($barW + $gap); @endphp
                        <rect x="{{ round($x, 2) }}" y="{{ round($h - $bh, 2) }}" width="{{ round($barW, 2) }}" height="{{ round(max($bh, $bucket['orders'] ? 2 : 0), 2) }}" rx="2" class="fill-brand-600">
                            <title>{{ $bucket['label'] }}: {{ price_admin($bucket['sales']) }} · {{ trans_choice('admin.customers.orders', $bucket['orders'], ['count' => $bucket['orders']]) }}</title>
                        </rect>
                        @if ($i % $labelEvery === 0)
                            @php $anchor = $i === 0 ? 'start' : ($i + $labelEvery >= $n ? 'end' : 'middle'); $lx = $anchor === 'start' ? $x : ($anchor === 'end' ? $x + $barW : $x + $barW / 2); @endphp
                            <text x="{{ round($lx, 2) }}" y="{{ $h + 18 }}" text-anchor="{{ $anchor }}" class="fill-muted" font-size="11">{{ $bucket['label'] }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
            <p class="mt-2 text-xs text-muted">{{ __('admin.analytics.peak', ['amount' => price_admin(\App\Support\Money::ofMinor($max, config('store.base_currency')))]) }}</p>
        @endif
    </x-admin.card>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-admin.card :title="__('admin.analytics.top_products')" :padding="false" class="lg:col-span-2">
            @if (! $report['topProducts'])
                <p class="p-5 text-sm text-muted">{{ __('admin.analytics.no_sales') }}</p>
            @else
                <ol class="divide-y divide-line">
                    @foreach ($report['topProducts'] as $i => $row)
                        <li class="flex items-center gap-4 px-4 py-2.5 text-sm sm:px-5">
                            <span class="w-5 text-muted">{{ $i + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate">
                                @if ($row['product_id'])<a href="{{ route('admin.products.edit', $row['product_id']) }}" class="hover:underline">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif
                            </span>
                            <span class="text-ink-soft">{{ trans_choice('admin.analytics.units', $row['units'], ['count' => $row['units']]) }}</span>
                            <span class="w-28 text-end font-medium" dir="ltr">{{ price_admin($row['sales']) }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.analytics.customers')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.analytics.new_accounts') }}</dt><dd class="font-medium">{{ $report['newCustomers'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.analytics.returning') }}</dt><dd class="font-medium">{{ $report['returningRate'] === null ? '—' : $report['returningRate'].'%' }}</dd></div>
                </dl>
            </x-admin.card>

            @foreach (['countries' => fn ($k) => \App\Support\Countries::name($k), 'payments' => fn ($k) => __("admin.orders.methods.{$k}"), 'discounts' => fn ($k) => $k] as $group => $labelFor)
                @if ($report[$group])
                    <x-admin.card :title="__('admin.analytics.by_'.$group)">
                        <ul class="space-y-2.5 text-sm">
                            @foreach ($report[$group] as $row)
                                <li>
                                    <div class="flex justify-between gap-3">
                                        <span class="truncate">{{ $labelFor($row['key']) }}</span>
                                        <span class="text-ink-soft" dir="ltr">{{ $row['orders'] }} · {{ price_admin($row['amount']) }}</span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-canvas"><div class="h-full rounded-full bg-brand-600" style="width: {{ round($row['orders'] / max(1, $t['orders']) * 100) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    </x-admin.card>
                @endif
            @endforeach
        </div>
    </div>

    <p class="mt-6 text-xs text-muted">{{ __('admin.analytics.note') }}</p>

    {{-- Where people are lost between looking and paying. --}}
    <x-admin.card class="mt-6">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-[15px] font-semibold">{{ __('admin.analytics.funnel_title') }}</h2>
            <p class="text-[13px] text-muted">{{ __('admin.analytics.funnel_note') }}</p>
        </div>

        <div class="space-y-3">
            @foreach ($funnel['stages'] as $step)
                <div>
                    <div class="mb-1 flex items-baseline justify-between gap-3 text-[13px]">
                        <span class="font-medium text-ink-strong">{{ __('admin.analytics.stage_'.$step['stage']) }}</span>
                        <span class="text-ink-soft">
                            {{ number_format($step['count']) }}
                            @if ($step['of_previous'] !== null)
                                <span class="text-muted">· {{ $step['of_previous'] }}% {{ __('admin.analytics.of_previous') }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="h-2 w-full rounded bg-canvas">
                        <div class="h-2 rounded bg-brand-600" style="width: {{ max(1, (int) ($step['of_top'] ?? 0)) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (collect($funnel['stages'])->sum('count') === 0)
            <p class="mt-4 text-[13px] text-muted">{{ __('admin.analytics.funnel_empty') }}</p>
        @endif
    </x-admin.card>

    {{-- What people looked for and did not find. --}}
    <x-admin.card class="mt-6">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-[15px] font-semibold">{{ __('admin.analytics.misses_title') }}</h2>
            <p class="text-[13px] text-muted">{{ __('admin.analytics.misses_note') }}</p>
        </div>

        @forelse ($missedSearches as $miss)
            <div class="flex items-baseline justify-between gap-3 border-b border-line py-2 text-[13px] last:border-0">
                <span class="font-medium text-ink-strong">{{ $miss->term }}</span>
                <span class="text-muted">{{ number_format($miss->count) }} ×</span>
            </div>
        @empty
            <p class="text-[13px] text-muted">{{ __('admin.analytics.misses_empty') }}</p>
        @endforelse
    </x-admin.card>

@endsection
