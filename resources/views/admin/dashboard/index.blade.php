@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.nav.home'))

@section('content')
    @php
        $me = auth('admin')->user();
        $hour = (int) now(config('store.display_timezone'))->format('G');
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
        $tz = config('store.display_timezone');
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold tracking-tight text-ink-strong">{{ __("admin.home.greeting_{$greeting}", ['name' => \Illuminate\Support\Str::before($me->name, ' ')]) }}</h1>
        @if ($report)
            <a href="{{ route('admin.analytics') }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-surface px-2.5 text-[13px] font-medium shadow-button ring-1 ring-black/10 hover:bg-subdued">
                <x-icon name="calendar" class="size-4" /> {{ __('admin.home.last_30') }}
            </a>
        @endif
    </div>

    {{-- Key figures --}}
    @if ($report)
        @php
            $t = $report['totals']; $p = $report['previous'];
            $delta = fn (int $now, int $before) => $before ? (int) round(($now - $before) / $before * 100) : null;
            $spark = function (array $values) {
                $max = max(1, ...$values); $n = max(1, count($values) - 1);
                return collect($values)->map(fn ($v, $i) => round($i / $n * 100, 2).','.round(28 - $v / $max * 26, 2))->implode(' ');
            };
            $salesSeries = array_map(fn ($b) => $b['sales']->minor, $report['series']);
            $orderSeries = array_map(fn ($b) => $b['orders'], $report['series']);
            $kpis = [
                ['key' => 'sales', 'icon' => 'coins', 'value' => price_admin($t['sales']), 'delta' => $delta($t['sales']->minor, $p['sales']->minor), 'series' => $salesSeries],
                ['key' => 'orders', 'icon' => 'shopping-bag', 'value' => number_format($t['orders']), 'delta' => $delta($t['orders'], $p['orders']), 'series' => $orderSeries],
                ['key' => 'average', 'icon' => 'receipt', 'value' => price_admin($t['average']), 'delta' => $delta($t['average']->minor, $p['average']->minor), 'series' => null],
                ['key' => 'returning', 'icon' => 'users', 'value' => $report['returningRate'] === null ? '—' : $report['returningRate'].'%', 'delta' => null, 'series' => null],
            ];
        @endphp
        <a href="{{ route('admin.analytics') }}" class="admin-kpis mb-4 grid grid-cols-2 gap-3 rounded-2xl lg:grid-cols-4">
            @foreach ($kpis as $kpi)
                <div class="admin-kpi rounded-2xl p-4">
                    <x-icon :name="$kpi['icon']" class="admin-kpi-mark" />
                    <p class="flex items-center gap-2 text-[13px] font-medium">
                        <x-icon :name="$kpi['icon']" class="size-4 text-warning-100" />
                        {{ __("admin.home.kpi.{$kpi['key']}") }}
                    </p>
                    <div class="mt-1.5 flex items-end justify-between gap-2">
                        <p class="text-xl font-bold text-white" dir="ltr">{{ $kpi['value'] }}</p>
                        @if ($kpi['delta'] !== null)
                            <span @class(['mb-0.5 text-xs font-medium', 'text-success-100' => $kpi['delta'] >= 0, 'text-danger-100' => $kpi['delta'] < 0])>
                                {{ $kpi['delta'] >= 0 ? '↗' : '↘' }} {{ abs($kpi['delta']) }}%
                            </span>
                        @endif
                    </div>
                    @if ($kpi['series'])
                        <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="mt-2 h-8 w-full text-warning-100" aria-hidden="true" dir="ltr">
                            <polyline points="{{ $spark($kpi['series']) }}" fill="none" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
                        </svg>
                    @else
                        <div class="mt-2 h-8"></div>
                    @endif
                </div>
            @endforeach
        </a>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            {{-- Things to do --}}
            <x-admin.card :padding="false">
                @if ($todos)
                    <ul class="divide-y divide-line">
                        @foreach ($todos as $todo)
                            <li>
                                <a href="{{ $todo['url'] }}" class="flex items-center gap-3 px-4 py-3 hover:bg-subdued">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-subdued text-ink-soft"><x-icon :name="$todo['icon']" class="size-4" /></span>
                                    <span class="flex-1 text-sm font-semibold text-ink-strong">{{ trans_choice("admin.home.todo.{$todo['key']}", $todo['count'], ['count' => $todo['count']]) }}</span>
                                    <x-icon name="chevron-right" class="size-4 text-muted rtl:rotate-180" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="flex items-center gap-3 px-4 py-4">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-success-100 text-success-700"><x-icon name="circle-check" class="size-4" /></span>
                        <p class="text-sm font-medium">{{ __('admin.home.all_done') }}</p>
                    </div>
                @endif
            </x-admin.card>

            {{-- Recent orders --}}
            @if ($recentOrders->isNotEmpty())
                <x-admin.card :title="__('admin.home.recent_orders')" :padding="false">
                    <x-slot:actions><x-admin.button variant="plain" :href="route('admin.orders.index')">{{ __('admin.home.view_all') }}</x-admin.button></x-slot:actions>
                    <div class="mt-3 overflow-x-auto"><table class="admin-table">
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td class="whitespace-nowrap"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-ink-strong hover:underline" dir="ltr">{{ $order->number }}</a></td>
                                    <td class="max-w-40 truncate text-ink-soft">{{ $order->ship_name }}</td>
                                    <td class="hidden whitespace-nowrap text-ink-soft sm:table-cell">{{ $order->placed_at->timezone($tz)->translatedFormat('j M, H:i') }}</td>
                                    <td><x-admin.badge :tone="$order->fulfillment_status->value === 'unfulfilled' && ! $order->isCancelled() ? 'attention' : 'neutral'" :dot="$order->fulfillment_status->value === 'unfulfilled' ? 'empty' : 'full'">{{ $order->isCancelled() ? __('admin.orders.cancelled') : $order->fulfillment_status->label() }}</x-admin.badge></td>
                                    <td class="text-end whitespace-nowrap" dir="ltr">{{ price_admin($order->grand_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </x-admin.card>
            @endif
        </div>

        <div class="min-w-0 space-y-4">
            {{-- Setup guide (owners, until done) --}}
            @if ($me->mayAccess('settings') && $completedSteps < count($setupSteps))
                <x-admin.card :title="__('admin.home.setup_title')" :description="__('admin.home.setup_progress', ['done' => $completedSteps, 'total' => count($setupSteps)])">
                    <div class="mb-3 h-1.5 overflow-hidden rounded-full bg-subdued"><div class="h-full rounded-full bg-ink-strong" style="width: {{ round($completedSteps / count($setupSteps) * 100) }}%"></div></div>
                    <ul class="-mx-2 space-y-0.5">
                        @foreach ($setupSteps as $step)
                            <li>
                                <a href="{{ $step['url'] }}" class="flex items-start gap-2.5 rounded-lg px-2 py-2 hover:bg-subdued">
                                    @if ($step['done'])
                                        <x-icon name="circle-check" class="mt-px size-4 text-success-600" />
                                    @else
                                        <span class="mt-px size-4 rounded-full border-[1.5px] border-dashed border-muted"></span>
                                    @endif
                                    <span class="min-w-0">
                                        <span @class(['block text-[13px] font-semibold', 'text-muted line-through decoration-line-strong' => $step['done'], 'text-ink-strong' => ! $step['done']])>{{ $step['title'] }}</span>
                                        @unless ($step['done'])<span class="block text-xs text-muted">{{ $step['text'] }}</span>@endunless
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif

            <x-admin.card :title="__('admin.home.store_title')">
                <p class="text-[13px] text-muted">{{ __('admin.home.store_text') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-admin.button variant="secondary" size="sm" :href="store_url('/', config('store.default_locale'))" target="_blank" rel="noopener" icon="external-link">{{ __('admin.nav.view_store') }}</x-admin.button>
                    @if ($me->mayAccess('catalog'))<x-admin.button variant="secondary" size="sm" :href="route('admin.products.create')" icon="plus">{{ __('admin.search.go.add_product') }}</x-admin.button>@endif
                </div>
            </x-admin.card>
        </div>
    </div>
@endsection
