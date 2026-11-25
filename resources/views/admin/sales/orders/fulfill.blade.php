@extends('admin.layouts.app')

@section('title', __('admin.fulfill.title'))

@section('content')
    <form method="POST" action="{{ route('admin.orders.fulfillments.store', $order) }}"
          x-data="{ qty: @js(collect($left)->filter()->map(fn ($q, $id) => (int) old('items.'.$id, $q))->all()), max: @js($left) }">
        @csrf
        <x-admin.page-header :title="__('admin.fulfill.title')" :back="route('admin.orders.show', $order)" :subtitle="$order->number" />

        @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="min-w-0 space-y-4 lg:col-span-2">
                <x-admin.card :padding="false">
                    <header class="flex items-center gap-2 px-4 pt-4">
                        <x-admin.badge tone="attention" dot="empty">{{ __('admin.fulfill.unfulfilled') }}</x-admin.badge>
                        <span class="text-[13px] text-muted">{{ $order->shipping_method_name }}</span>
                    </header>
                    <ul class="mt-3 divide-y divide-line border-t border-line">
                        @foreach ($order->items as $item)
                            @continue(($left[$item->id] ?? 0) === 0)
                            <li class="flex items-center gap-3 px-4 py-3">
                                @if ($item->image)<img src="{{ $item->image }}" alt="" class="size-10 rounded-lg border border-line object-cover">@endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-ink-strong">{{ $item->name }}</span>
                                    <span class="block text-xs text-muted">{{ $item->variant_title }} @if ($item->sku)<span dir="ltr">· {{ $item->sku }}</span>@endif</span>
                                </span>
                                <label class="flex items-center gap-1.5 text-sm">
                                    <span class="sr-only">{{ __('admin.fulfill.quantity_for', ['name' => $item->name]) }}</span>
                                    <input type="number" name="items[{{ $item->id }}]" value="{{ old('items.'.$item->id, $left[$item->id]) }}" x-model.number="qty[{{ $item->id }}]" min="0" max="{{ $left[$item->id] }}" class="admin-field w-16 text-center" dir="ltr">
                                    <span class="text-muted">{{ __('admin.fulfill.of', ['count' => $left[$item->id]]) }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>

                <x-admin.card :title="__('admin.fulfill.tracking_title')">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-admin.input name="tracking_number" :label="__('admin.fulfill.tracking_number')" dir="ltr" maxlength="100" />
                        <div>
                            <x-admin.input name="carrier" :label="__('admin.fulfill.carrier')" list="carriers" maxlength="60" />
                            <datalist id="carriers">
                                @foreach (['Aramex', 'Emirates Post', 'DHL', 'SMSA', 'Fetchr', 'Quiqup', __('admin.fulfill.own_delivery')] as $carrier)<option value="{{ $carrier }}">@endforeach
                            </datalist>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <div class="min-w-0 space-y-4">
                <x-admin.card :title="__('admin.fulfill.summary')">
                    <p class="text-[13px] text-muted">{{ __('admin.fulfill.shipping_to') }}</p>
                    <p class="mt-1 text-sm leading-5">{{ $order->ship_name }}<br>{{ $order->ship_address_line }}<br>{{ $order->shipRegionLine() }}</p>
                    <div class="mt-4 border-t border-line pt-3 text-sm">
                        <span x-text="@js(__('admin.fulfill.count')).replace(':count', Object.values(qty).reduce((a, b) => a + (+b || 0), 0))"></span>
                    </div>
                    <label class="mt-4 flex items-start gap-2 text-[13px]">
                        <input type="checkbox" name="notify" value="1" checked class="admin-checkbox mt-0.5">
                        <span>{{ __('admin.fulfill.notify') }}</span>
                    </label>
                    <x-admin.button class="mt-4 w-full" x-bind:disabled="! Object.values(qty).some((q) => +q > 0)">{{ __('admin.fulfill.submit') }}</x-admin.button>
                </x-admin.card>
            </div>
        </div>
    </form>
    {{-- Book the courier instead of typing a tracking number by hand. Only when an Aramex
         account is configured (Settings → Shipping and delivery). --}}
    @if (app(\App\Services\Shipping\AramexCarrier::class)->isConfigured())
        <form method="POST" action="{{ route('admin.orders.shipment', $order) }}" class="mt-6 space-y-3 border-t border-line pt-6">
            @csrf
            <p class="text-[13px] font-medium">{{ __('admin.shipping.book_shipment') }}</p>
            <div class="flex flex-wrap items-end gap-3">
                <label class="block">
                    <span class="mb-1 block text-[13px] text-ink-soft">{{ __('admin.shipping.weight_kg') }}</span>
                    <input type="number" name="weight_kg" step="0.1" min="0.1" max="500" value="10" required class="admin-field w-28">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[13px] text-ink-soft">{{ __('admin.shipping.pieces') }}</span>
                    <input type="number" name="pieces" min="1" max="50" value="1" class="admin-field w-24">
                </label>
                <label class="flex items-center gap-2 pb-2 text-sm">
                    <input type="checkbox" name="notify" value="1" checked class="size-4 accent-brand-600">
                    {{ __('admin.fulfill.notify') }}
                </label>
                <x-admin.button icon="truck">{{ __('admin.shipping.book_shipment') }}</x-admin.button>
            </div>
        </form>
    @endif

    {{-- Labels for shipments already booked. --}}
    @foreach ($order->fulfillments()->whereNotNull('tracking_number')->get() as $shipment)
        <p class="mt-3 flex flex-wrap items-center gap-3 text-[13px] text-ink-soft">
            <span>{{ $shipment->carrier }} · {{ $shipment->tracking_number }}</span>
            <a href="{{ route('admin.fulfillments.label', $shipment) }}" target="_blank" rel="noopener" class="font-medium text-brand-600">{{ __('admin.shipping.print_label') }}</a>
        </p>
    @endforeach

@endsection
