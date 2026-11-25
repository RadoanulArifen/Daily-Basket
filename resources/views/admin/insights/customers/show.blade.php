@extends('admin.layouts.app')

@section('title', $customer->name)

@section('content')
    @php
        $tz = config('store.display_timezone');
        $currency = config('store.base_currency');
        $spentMoney = \App\Support\Money::of(number_format($spent, 2, '.', ''), $currency);
        $average = $salesCount ? \App\Support\Money::ofMinor(intdiv($spentMoney->minor, $salesCount), $currency) : null;
        $since = $customer->created_at->timezone($tz);
    @endphp

    <x-admin.page-header :title="$customer->name" :back="route('admin.customers.index')"
        :subtitle="__('admin.customers.customer_for', ['time' => $since->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE)])">
        <x-slot:badges>
            @unless ($customer->is_active)<x-admin.badge tone="danger">{{ __('admin.customers.blocked_badge') }}</x-admin.badge>@endunless
        </x-slot:badges>
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.draft-orders.create')">{{ __('admin.draft.title') }}</x-admin.button>
            <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                <x-admin.button type="button" variant="secondary" x-on:click="open = ! open">{{ __('admin.orders.more_actions') }} <x-icon name="chevron-down" class="size-3.5" /></x-admin.button>
                <div x-show="open" x-cloak @click.outside="open = false" class="absolute end-0 z-20 mt-1 w-56 rounded-xl bg-surface p-1.5 shadow-popover" role="menu">
                    <a href="mailto:{{ $customer->email }}" role="menuitem" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] hover:bg-subdued"><x-icon name="mail" class="size-4 text-muted" />{{ __('admin.customers.send_email') }}</a>
                    <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" @if ($customer->is_active) x-data @submit="if (! confirm(@js(__('admin.customers.block_confirm')))) $event.preventDefault()" @endif>
                        @csrf
                        <button role="menuitem" @class(['flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px]', 'text-danger-600 hover:bg-danger-50' => $customer->is_active, 'hover:bg-subdued' => ! $customer->is_active])>
                            <x-icon :name="$customer->is_active ? 'circle-x' : 'circle-check'" class="size-4" />{{ $customer->is_active ? __('admin.customers.block') : __('admin.customers.unblock') }}
                        </button>
                    </form>
                </div>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            {{-- Stats --}}
            <div class="grid grid-cols-2 overflow-hidden rounded-xl bg-surface shadow-card sm:grid-cols-4">
                @foreach ([
                    [__('admin.customers.stats.spent'), price_admin($spentMoney)],
                    [__('admin.customers.stats.orders'), $salesCount],
                    [__('admin.customers.stats.average'), $average ? price_admin($average) : '—'],
                    [__('admin.customers.stats.since'), $since->translatedFormat('j M Y')],
                ] as $i => [$label, $value])
                    <div @class(['p-4', 'border-s border-line' => $i % 2 === 1 || $i === 2, 'max-sm:border-t max-sm:border-s-0' => $i === 2, 'max-sm:border-t' => $i === 3])>
                        <p class="text-[13px] text-muted">{{ $label }}</p>
                        <p class="mt-1 text-base font-semibold text-ink-strong" dir="ltr">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Last order --}}
            @if ($lastOrder)
                <x-admin.card :padding="false">
                    <header class="flex flex-wrap items-center gap-2 px-4 pt-4">
                        <h2 class="text-sm font-semibold text-ink-strong">{{ __('admin.customers.last_order') }}</h2>
                        <a href="{{ route('admin.orders.show', $lastOrder) }}" class="text-[13px] font-semibold text-brand-600 hover:underline" dir="ltr">{{ $lastOrder->number }}</a>
                        <x-admin.badge :tone="$lastOrder->payment_status->value === 'pending' ? 'warning' : 'neutral'" :dot="$lastOrder->payment_status->value === 'paid' ? 'full' : 'half'">{{ $lastOrder->payment_status->label() }}</x-admin.badge>
                        <x-admin.badge :tone="in_array($lastOrder->fulfillment_status->value, ['unfulfilled', 'partial']) && ! $lastOrder->isCancelled() ? 'attention' : 'neutral'" :dot="$lastOrder->fulfillment_status->value === 'unfulfilled' ? 'empty' : 'full'">{{ $lastOrder->fulfillment_status->label() }}</x-admin.badge>
                        <span class="ms-auto text-[13px] text-muted">{{ $lastOrder->placed_at->timezone($tz)->translatedFormat('j M Y, H:i') }}</span>
                    </header>
                    @include('admin.sales.orders.partials.lines', ['lines' => $lastOrder->items->map(fn ($i) => [$i, $i->quantity])])
                    <div class="flex items-center justify-between border-t border-line px-4 py-3 text-sm">
                        <span class="font-semibold" dir="ltr">{{ price_admin($lastOrder->grand_total) }}</span>
                        <x-admin.button variant="plain" :href="route('admin.orders.index', ['q' => $customer->email])">{{ __('admin.customers.view_all_orders') }}</x-admin.button>
                    </div>
                </x-admin.card>
            @endif

            {{-- All orders --}}
            <x-admin.card :title="__('admin.orders.title')" :padding="false">
                @if ($orders->isEmpty())
                    <p class="px-4 pb-4 pt-2 text-[13px] text-muted">{{ __('admin.customers.no_orders') }}</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="admin-table">
                            <thead><tr><th>{{ __('admin.orders.cols.order') }}</th><th>{{ __('admin.orders.cols.date') }}</th><th>{{ __('admin.orders.cols.fulfillment') }}</th><th class="text-end">{{ __('admin.orders.cols.total') }}</th></tr></thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-ink-strong hover:underline" dir="ltr">{{ $order->number }}</a></td>
                                        <td class="whitespace-nowrap text-ink-soft">{{ $order->placed_at->timezone($tz)->translatedFormat('j M Y') }}</td>
                                        <td>@if ($order->isCancelled())<x-admin.badge dot="empty">{{ __('admin.orders.cancelled') }}</x-admin.badge>@else<x-admin.badge :tone="in_array($order->fulfillment_status->value, ['unfulfilled', 'partial']) ? 'attention' : 'neutral'" :dot="$order->fulfillment_status->value === 'unfulfilled' ? 'empty' : 'full'">{{ $order->fulfillment_status->label() }}</x-admin.badge>@endif</td>
                                        <td class="text-end whitespace-nowrap" dir="ltr">{{ price_admin($order->grand_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>

            {{-- Timeline --}}
            <div>
                <h2 class="mb-2 px-1 text-sm font-semibold text-ink-strong">{{ __('admin.orders.timeline') }}</h2>
                <ol class="relative ms-4 space-y-4 border-s-2 border-line ps-6">
                    @foreach ($orders->take(10) as $order)
                        <li class="relative">
                            <span class="absolute -start-[1.95rem] top-1 size-2.5 rounded-full border-2 border-canvas bg-muted"></span>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                <p class="text-[13px]">{!! __('admin.customers.timeline_order', ['number' => '<a href="'.e(route('admin.orders.show', $order)).'" class="font-medium text-brand-600 hover:underline">'.e($order->number).'</a>', 'total' => e(price_admin($order->grand_total))]) !!}</p>
                                <time class="text-xs text-muted">{{ $order->placed_at->timezone($tz)->translatedFormat('j M Y, H:i') }}</time>
                            </div>
                        </li>
                    @endforeach
                    <li class="relative">
                        <span class="absolute -start-[1.95rem] top-1 size-2.5 rounded-full border-2 border-canvas bg-muted"></span>
                        <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                            <p class="text-[13px]">{{ __('admin.customers.timeline_joined') }}</p>
                            <time class="text-xs text-muted">{{ $since->translatedFormat('j M Y, H:i') }}</time>
                        </div>
                    </li>
                </ol>
            </div>
        </div>

        <div class="min-w-0 space-y-4">
            <x-admin.card :padding="false">
                <div class="px-4 pt-4">
                    <h2 class="text-sm font-semibold text-ink-strong">{{ __('admin.customers.customer') }}</h2>
                    <p class="mt-2 text-[13px]"><a href="mailto:{{ $customer->email }}" class="text-brand-600 hover:underline">{{ $customer->email }}</a></p>
                    @if ($customer->phone)<p class="text-[13px]"><a href="tel:{{ $customer->phone }}" class="text-brand-600 hover:underline" dir="ltr">{{ $customer->phone }}</a></p>@endif
                    <p class="mt-1 text-xs text-muted">{{ $customer->locale === 'ar' ? 'العربية' : 'English' }} · {{ $marketing ? __('admin.orders.opted_in') : __('admin.orders.not_opted_in') }}</p>
                    @if ($customer->last_login_at)<p class="mt-1 text-xs text-muted">{{ __('admin.customers.last_login', ['date' => $customer->last_login_at->timezone($tz)->translatedFormat('j M Y, H:i')]) }}</p>@endif
                </div>
                <div class="mt-3 border-t border-line px-4 py-3">
                    <h3 class="text-[13px] font-semibold">{{ __('admin.customers.default_address_title') }}</h3>
                    @if ($address = $customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first())
                        <address class="mt-1 text-[13px] leading-5 not-italic">{{ $address->name }}<br>{{ $address->address_line }}@if ($address->building), {{ $address->building }}@endif<br>{{ $address->regionLine() }}<br><span dir="ltr">{{ $address->phone }}</span></address>
                        @if ($customer->addresses->count() > 1)
                            <details class="mt-2 text-[13px]">
                                <summary class="cursor-pointer font-medium text-brand-600">{{ trans_choice('admin.customers.other_addresses', $customer->addresses->count() - 1, ['count' => $customer->addresses->count() - 1]) }}</summary>
                                @foreach ($customer->addresses->reject(fn ($a) => $a->is($address)) as $other)
                                    <address class="mt-2 border-t border-line pt-2 leading-5 not-italic"><span class="font-medium">{{ $other->label ?: $other->name }}</span><br>{{ $other->address_line }}, {{ $other->regionLine() }}</address>
                                @endforeach
                            </details>
                        @endif
                    @else
                        <p class="mt-1 text-[13px] text-muted">{{ __('admin.customers.no_address') }}</p>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.customers.notes')">
                <form method="POST" action="{{ route('admin.customers.note', $customer) }}" x-data="{ changed: false }">
                    @csrf @method('PUT')
                    <label for="admin_note" class="sr-only">{{ __('admin.customers.notes') }}</label>
                    <textarea id="admin_note" name="admin_note" rows="3" maxlength="2000" @input="changed = true" placeholder="{{ __('admin.customers.notes_placeholder') }}" class="admin-field">{{ $customer->admin_note }}</textarea>
                    <p class="mt-1 text-xs text-muted">{{ __('admin.customers.notes_help') }}</p>
                    <div x-show="changed" x-cloak class="mt-2 flex justify-end"><x-admin.button size="sm">{{ __('admin.common.save') }}</x-admin.button></div>
                </form>
            </x-admin.card>

            @if ($reviewCount || $wishlistCount)
                <x-admin.card :title="__('admin.customers.activity')">
                    <dl class="space-y-1.5 text-[13px]">
                        <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.customers.stats.wishlist') }}</dt><dd>{{ $wishlistCount }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.customers.stats.reviews') }}</dt><dd>{{ $reviewCount }}</dd></div>
                    </dl>
                </x-admin.card>
            @endif
        </div>
    </div>
@endsection
