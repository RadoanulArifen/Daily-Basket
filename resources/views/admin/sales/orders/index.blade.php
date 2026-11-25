@extends('admin.layouts.app')

@section('title', __('admin.orders.title'))
@section('fullWidth', true)

@section('content')
    @php
        $tz = config('store.display_timezone');
        $today = now($tz)->startOfDay();
    @endphp

    <x-admin.page-header :title="__('admin.orders.title')">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.returns.index')" icon="undo-2">{{ __('admin.nav.returns') }}</x-admin.button>
            <x-admin.button :href="route('admin.draft-orders.create')">{{ __('admin.draft.title') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div x-data="indexFilters({ ids: @js($orders->pluck('id')) })" class="overflow-hidden rounded-xl bg-surface shadow-card">
        <x-admin.index-filters resource="orders" route="admin.orders.index" :placeholder="__('admin.orders.search')"
            :tabs="[
                ['key' => 'all', 'label' => __('admin.orders.tabs.all')],
                ['key' => 'unfulfilled', 'label' => __('admin.orders.tabs.unfulfilled'), 'count' => $counts['unfulfilled']],
                ['key' => 'unpaid', 'label' => __('admin.orders.tabs.unpaid'), 'count' => $counts['unpaid']],
                ['key' => 'open', 'label' => __('admin.orders.tabs.open')],
                ['key' => 'cancelled', 'label' => __('admin.orders.tabs.cancelled')],
            ]"
            :sorts="collect(\App\Http\Controllers\Admin\Sales\OrderController::SORTS)->mapWithKeys(fn ($s) => [$s => __('admin.orders.sort.'.$s)])->all()">
            <x-slot:filters>
                <x-admin.filter-pill name="payment" :label="__('admin.orders.filters.payment')" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                <x-admin.filter-pill name="fulfillment" :label="__('admin.orders.filters.fulfillment')" :options="collect(\App\Enums\FulfillmentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                <x-admin.filter-pill name="method" :label="__('admin.orders.filters.method')" :options="['cod' => __('admin.orders.methods.cod'), 'card' => __('admin.orders.methods.stripe')]" />
                <x-admin.filter-pill name="date" :label="__('admin.orders.filters.date')" :options="['today' => __('admin.orders.dates.today'), '7' => __('admin.orders.dates.7'), '30' => __('admin.orders.dates.30'), '90' => __('admin.orders.dates.90')]" />
            </x-slot:filters>
        </x-admin.index-filters>

        @if ($orders->isEmpty())
            <x-admin.empty-state icon="inbox" :title="__('admin.orders.empty_title')" :text="__('admin.orders.empty_text')" />
        @else
            {{-- Bulk actions --}}
            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center gap-3 border-b border-line bg-subdued px-3 py-2">
                <span class="text-[13px] font-semibold" x-text="@js(__('admin.orders.selected')).replace(':count', selected.length)"></span>
                <form method="POST" action="{{ route('admin.orders.bulk') }}" class="flex items-center gap-2">
                    @csrf
                    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <input type="hidden" name="action" value="fulfill">
                    <x-admin.button variant="secondary" size="sm" icon="package-check">{{ __('admin.orders.bulk_fulfill') }}</x-admin.button>
                </form>
                <button type="button" @click="clear()" class="ms-auto text-[13px] font-medium text-brand-600 hover:underline">{{ __('admin.common.clear_selection') }}</button>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10"><input type="checkbox" class="admin-checkbox" :checked="all" :indeterminate="some" @change="toggleAll()" aria-label="{{ __('admin.common.select_all') }}"></th>
                            <th>{{ __('admin.orders.cols.order') }}</th>
                            <th>{{ __('admin.orders.cols.date') }}</th>
                            <th>{{ __('admin.orders.cols.customer') }}</th>
                            <th class="text-end">{{ __('admin.orders.cols.total') }}</th>
                            <th>{{ __('admin.orders.cols.payment') }}</th>
                            <th>{{ __('admin.orders.cols.fulfillment') }}</th>
                            <th>{{ __('admin.orders.cols.items') }}</th>
                            <th>{{ __('admin.orders.cols.delivery') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $placed = $order->placed_at->timezone($tz);
                                $url = route('admin.orders.show', $order);
                            @endphp
                            <tr @if ($loop->first) data-tour="order-row" @endif :data-selected="selected.includes({{ $order->id }})" class="cursor-pointer" x-data @click="if (! $event.target.closest('a,input,button')) window.location = @js($url)">
                                <td><input type="checkbox" class="admin-checkbox" value="{{ $order->id }}" x-model.number="selected" aria-label="{{ __('admin.common.select_row', ['name' => $order->number]) }}"></td>
                                <td class="whitespace-nowrap"><a href="{{ $url }}" class="font-semibold text-ink-strong hover:underline" dir="ltr">{{ $order->number }}</a></td>
                                <td class="whitespace-nowrap text-ink-soft">{{ $placed->isSameDay($today) ? __('admin.orders.today_at', ['time' => $placed->format('H:i')]) : $placed->translatedFormat('j M \a\t H:i') }}</td>
                                <td class="max-w-48 truncate">{{ $order->ship_name }}</td>
                                <td class="text-end whitespace-nowrap" dir="ltr">{{ price_admin($order->grand_total) }}</td>
                                <td>
                                    @if ($order->isCancelled() && $order->payment_status === \App\Enums\PaymentStatus::Voided)
                                        <x-admin.badge dot="empty">{{ __('admin.orders.cancelled') }}</x-admin.badge>
                                    @else
                                        <x-admin.badge :tone="$order->payment_status === \App\Enums\PaymentStatus::Paid ? 'neutral' : ($order->payment_status === \App\Enums\PaymentStatus::Pending ? 'warning' : 'neutral')"
                                            :dot="$order->payment_status === \App\Enums\PaymentStatus::Paid ? 'full' : 'half'">{{ $order->payment_status->label() }}</x-admin.badge>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.badge :tone="match ($order->fulfillment_status) { \App\Enums\FulfillmentStatus::Unfulfilled => $order->isCancelled() ? 'neutral' : 'attention', default => 'neutral' }"
                                        :dot="$order->fulfillment_status === \App\Enums\FulfillmentStatus::Unfulfilled ? 'empty' : 'full'">{{ $order->fulfillment_status->label() }}</x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap text-ink-soft">{{ trans_choice('admin.orders.item_count', (int) $order->units, ['count' => (int) $order->units]) }}</td>
                                <td class="max-w-40 truncate text-ink-soft">{{ $order->shipping_method_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links('components.admin.pagination') }}
        @endif
    </div>
@endsection
