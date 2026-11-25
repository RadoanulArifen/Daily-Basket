@extends('admin.layouts.app')

@section('title', __('admin.refund.title'))

@section('content')
    @php
        $rows = $order->items->map(fn ($i) => [
            'id' => $i->id,
            'unit' => $i->unit_price->minor,
            'max' => $i->quantity - ($refunded[$i->id] ?? 0),
        ])->values();
        $isCard = $order->payment_method !== 'cod';
    @endphp

    <form method="POST" action="{{ route('admin.orders.refunds.store', $order) }}"
          x-data="{
              rows: @js($rows), qty: {}, shipping: '0.00', amount: '0.00', touched: false,
              share: {{ $order->paidShareOfItems() }}, maxMinor: {{ $order->refundable()->minor }},
              suggest() {
                  let items = 0;
                  this.rows.forEach((r) => items += r.unit * Math.min(+this.qty[r.id] || 0, r.max));
                  let total = Math.round(items * this.share) + Math.round((parseFloat(this.shipping) || 0) * 100);
                  return Math.min(total, this.maxMinor);
              },
              sync() { if (! this.touched) this.amount = (this.suggest() / 100).toFixed(2); },
          }" x-init="$watch('qty', () => sync()); $watch('shipping', () => sync())">
        @csrf
        <x-admin.page-header :title="__('admin.refund.title')" :back="route('admin.orders.show', $order)" :subtitle="$order->number" />

        @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="min-w-0 space-y-4 lg:col-span-2">
                <x-admin.card :padding="false">
                    <ul class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            @php $max = $item->quantity - ($refunded[$item->id] ?? 0); @endphp
                            <li class="flex items-center gap-3 px-4 py-3">
                                @if ($item->image)<img src="{{ $item->image }}" alt="" class="size-10 rounded-lg border border-line object-cover">@endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-ink-strong">{{ $item->name }}</span>
                                    <span class="block text-xs text-muted">{{ $item->variant_title }} · <span dir="ltr">{{ price_admin($item->unit_price) }}</span></span>
                                </span>
                                @if ($max > 0)
                                    <label class="flex items-center gap-1.5 text-sm">
                                        <span class="sr-only">{{ __('admin.refund.quantity_for', ['name' => $item->name]) }}</span>
                                        <input type="number" name="items[{{ $item->id }}]" x-model="qty[{{ $item->id }}]" min="0" max="{{ $max }}" placeholder="0" class="admin-field w-16 text-center" dir="ltr">
                                        <span class="text-muted">{{ __('admin.fulfill.of', ['count' => $max]) }}</span>
                                    </label>
                                @else
                                    <x-admin.badge>{{ __('admin.refund.all_refunded') }}</x-admin.badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <label class="flex items-center gap-2 border-t border-line px-4 py-3 text-[13px]">
                        <input type="checkbox" name="restock" value="1" checked class="admin-checkbox"> {{ __('admin.refund.restock') }}
                    </label>
                </x-admin.card>

                @if (! $shippingLeft->isZero())
                    <x-admin.card :title="__('admin.refund.shipping_title')">
                        <div class="max-w-xs">
                            <x-admin.input name="shipping" type="number" step="0.01" min="0" :max="$shippingLeft->toDecimal()" :label="__('admin.refund.shipping_label')" x-model="shipping" dir="ltr"
                                :help="__('admin.refund.shipping_help', ['amount' => price_admin($shippingLeft)])" />
                        </div>
                    </x-admin.card>
                @endif

                <x-admin.card :title="__('admin.refund.reason_title')">
                    <x-admin.input name="reason" :label="__('admin.refund.reason_label')" maxlength="255" :help="__('admin.refund.reason_help')" />
                </x-admin.card>
            </div>

            <div class="min-w-0 space-y-4">
                <x-admin.card :title="__('admin.refund.summary')">
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.refund.paid') }}</dt><dd dir="ltr">{{ price_admin($order->grand_total) }}</dd></div>
                        @unless ($order->refunded_total->isZero())
                            <div class="flex justify-between"><dt class="text-ink-soft">{{ __('admin.refund.already') }}</dt><dd dir="ltr">−{{ price_admin($order->refunded_total) }}</dd></div>
                        @endunless
                        <div class="flex justify-between font-semibold"><dt>{{ __('admin.refund.available') }}</dt><dd dir="ltr">{{ price_admin($order->refundable()) }}</dd></div>
                    </dl>
                    <div class="mt-4 border-t border-line pt-4">
                        <x-admin.input name="amount" type="number" step="0.01" min="0" :max="$order->refundable()->toDecimal()" :label="__('admin.refund.amount')" x-model="amount" x-on:input="touched = true" :prefix="$order->currency" dir="ltr" required />
                        <p class="mt-1.5 text-xs text-muted">{{ $isCard ? __('admin.refund.via_card') : __('admin.refund.via_cash') }}</p>
                    </div>
                    <label class="mt-4 flex items-start gap-2 text-[13px]">
                        <input type="checkbox" name="notify" value="1" checked class="admin-checkbox mt-0.5">
                        <span>{{ __('admin.refund.notify') }}</span>
                    </label>
                    <x-admin.button class="mt-4 w-full"><span x-text="@js(__('admin.refund.submit')).replace(':amount', @js($order->currency) + ' ' + (parseFloat(amount) || 0).toFixed(2))">{{ __('admin.refund.title') }}</span></x-admin.button>
                </x-admin.card>
            </div>
        </div>
    </form>
@endsection
