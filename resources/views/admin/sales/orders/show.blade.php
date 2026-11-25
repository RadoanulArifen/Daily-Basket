@extends('admin.layouts.app')

@section('title', $order->number)

@section('content')
    @php
        $tz = config('store.display_timezone');
        $isCod = $order->payment_method === 'cod';
        $fulfillments = app(\App\Services\Orders\FulfillmentService::class);
        $left = $fulfillments->unfulfilled($order);
        $leftCount = array_sum($left);
        $PS = \App\Enums\PaymentStatus::class;
        $moneyIn = in_array($order->payment_status, [$PS::Paid, $PS::PartiallyRefunded, $PS::Refunded], true);
        $canMarkPaid = ! $order->isCancelled() && $isCod && $order->payment_status === $PS::Pending;
        $canRefund = in_array($order->payment_status, [$PS::Paid, $PS::PartiallyRefunded], true);
        $canCancel = ! $order->isCancelled() && $order->fulfillment_status !== \App\Enums\FulfillmentStatus::Delivered;
        $customerOrders = $order->user_id ? \App\Models\Order::query()->where('user_id', $order->user_id)->count() : \App\Models\Order::query()->where('email', $order->email)->count();
        $paymentBadge = fn () => match ($order->payment_status) {
            $PS::Paid => ['neutral', 'full'], $PS::Pending => ['warning', 'half'], $PS::PartiallyRefunded => ['warning', 'half'], default => ['neutral', 'empty'],
        };
        [$payTone, $payDot] = $paymentBadge();
    @endphp

    <x-admin.page-header :title="$order->number" :back="route('admin.orders.index')"
        :subtitle="__('admin.orders.placed_from', ['date' => $order->placed_at->timezone($tz)->translatedFormat('j F Y \a\t H:i')])">
        <x-slot:badges>
            @if ($order->isCancelled())<x-admin.badge>{{ __('admin.orders.cancelled') }}</x-admin.badge>@endif
            <x-admin.badge :tone="$payTone" :dot="$payDot">{{ $order->payment_status->label() }}</x-admin.badge>
            <x-admin.badge :tone="in_array($order->fulfillment_status->value, ['unfulfilled', 'partial']) && ! $order->isCancelled() ? 'attention' : 'neutral'"
                :dot="match ($order->fulfillment_status->value) { 'unfulfilled' => 'empty', 'partial' => 'half', default => 'full' }">{{ $order->fulfillment_status->label() }}</x-admin.badge>
        </x-slot:badges>
        <x-slot:actions>
            @if ($canRefund)<x-admin.button variant="secondary" :href="route('admin.orders.refunds.create', $order)">{{ __('admin.refund.title') }}</x-admin.button>@endif
            <x-admin.button variant="secondary" :href="route('admin.orders.invoice', $order)" target="_blank" icon="printer">{{ __('admin.orders.print') }}</x-admin.button>
            <div data-tour="order-more" x-data="{ open: false, cancelling: false }" class="relative" @keydown.escape="open = false; cancelling = false">
                <x-admin.button type="button" variant="secondary" x-on:click="open = ! open">{{ __('admin.orders.more_actions') }} <x-icon name="chevron-down" class="size-3.5" /></x-admin.button>
                <div x-show="open" x-cloak @click.outside="open = false" class="absolute end-0 z-20 mt-1 w-60 rounded-xl bg-surface p-1.5 shadow-popover" role="menu">
                    @if ($canMarkPaid)
                        <form method="POST" action="{{ route('admin.orders.paid', $order) }}">@csrf<button role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] hover:bg-subdued"><x-icon name="coins" class="size-4 text-muted" />{{ __('admin.orders.mark_paid') }}</button></form>
                    @endif
                    @if ($canRefund && ! $isCod)
                        <form method="POST" action="{{ route('admin.orders.refund', $order) }}" x-data @submit="if (! confirm(@js(__('admin.orders.refund_confirm')))) $event.preventDefault()">@csrf<button role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] hover:bg-subdued"><x-icon name="receipt" class="size-4 text-muted" />{{ __('admin.orders.mark_refunded') }}</button></form>
                    @endif
                    @if ($order->awaitsOnlinePayment() || $canMarkPaid)
                        {{-- A link to pay, while the order is still unpaid. --}}
                        <form method="POST" action="{{ route('admin.orders.email', $order) }}">
                            @csrf
                            <input type="hidden" name="what" value="payment">
                            <button role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] hover:bg-subdued"><x-icon name="credit-card" class="size-4" />{{ __('admin.orders.email_payment') }}</button>
                        </form>
                    @endif

                    {{-- Email the customer again: a bounced confirmation, or a copy on request. --}}
                    <form method="POST" action="{{ route('admin.orders.email', $order) }}">
                        @csrf
                        <input type="hidden" name="what" value="confirmation">
                        <button role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] hover:bg-subdued"><x-icon name="mail" class="size-4" />{{ __('admin.orders.email_confirmation') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.orders.email', $order) }}">
                        @csrf
                        <input type="hidden" name="what" value="invoice">
                        <button role="menuitem" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] hover:bg-subdued"><x-icon name="send" class="size-4" />{{ __('admin.orders.email_invoice') }}</button>
                    </form>
                    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" role="menuitem" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] hover:bg-subdued"><x-icon name="printer" class="size-4 text-muted" />{{ __('admin.orders.invoice') }}</a>
                    @if ($canCancel)
                        <div class="my-1 border-t border-line"></div>
                        <button type="button" role="menuitem" @click="open = false; cancelling = true" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-[13px] text-danger-600 hover:bg-danger-50"><x-icon name="circle-x" class="size-4" />{{ __('admin.orders.cancel_order') }}</button>
                    @endif
                </div>

                {{-- Cancel dialog --}}
                @if ($canCancel)
                    <div x-show="cancelling" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
                        <div class="absolute inset-0 bg-black/40" @click="cancelling = false"></div>
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="relative w-full max-w-md overflow-hidden rounded-xl bg-surface shadow-popover">
                            @csrf
                            <div class="border-b border-line px-4 py-3"><h2 id="cancel-title" class="text-sm font-semibold">{{ __('admin.orders.cancel_title_n', ['number' => $order->number]) }}</h2></div>
                            <div class="space-y-3 p-4">
                                <p class="text-[13px] text-ink-soft">{{ __('admin.orders.cancel_text') }}</p>
                                <x-admin.input name="reason" :label="__('admin.orders.cancel_reason')" maxlength="255" />
                                <label class="flex items-center gap-2 text-[13px]"><input type="hidden" name="restock" value="0"><input type="checkbox" name="restock" value="1" checked class="admin-checkbox"> {{ __('admin.orders.restock') }}</label>
                                @if ($moneyIn && ! $isCod)<x-admin.alert tone="warning">{{ __('admin.orders.cancel_refund_note') }}</x-admin.alert>@endif
                            </div>
                            <div class="flex justify-end gap-2 border-t border-line bg-subdued px-4 py-3">
                                <x-admin.button type="button" variant="secondary" x-on:click="cancelling = false">{{ __('admin.orders.keep_order') }}</x-admin.button>
                                <x-admin.button variant="danger">{{ __('admin.orders.cancel_order') }}</x-admin.button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            {{-- Unfulfilled --}}
            @if ($leftCount > 0 && ! $order->isCancelled())
                <x-admin.card data-tour="order-fulfill" :padding="false">
                    <header class="flex items-center gap-2 px-4 pt-4">
                        <x-admin.badge tone="attention" dot="empty">{{ __('admin.fulfill.unfulfilled_n', ['count' => $leftCount]) }}</x-admin.badge>
                        <span class="text-[13px] text-muted">{{ $order->shipping_method_name }}</span>
                    </header>
                    @include('admin.sales.orders.partials.lines', ['lines' => $order->items->filter(fn ($i) => ($left[$i->id] ?? 0) > 0)->map(fn ($i) => [$i, $left[$i->id]])])
                    <div class="flex justify-end gap-2 border-t border-line px-4 py-3">
                        @if ($order->awaitsOnlinePayment())
                            <p class="me-auto self-center text-[13px] text-muted">{{ __('admin.fulfill.errors.unpaid') }}</p>
                        @endif
                        <x-admin.button :href="$order->awaitsOnlinePayment() ? null : route('admin.orders.fulfill', $order)" :disabled="$order->awaitsOnlinePayment()">{{ __('admin.fulfill.button') }}</x-admin.button>
                    </div>
                </x-admin.card>
            @endif

            {{-- Shipments --}}
            @foreach ($order->fulfillments as $fulfillment)
                <x-admin.card :padding="false">
                    <header class="flex flex-wrap items-center gap-2 px-4 pt-4">
                        <x-admin.badge :tone="$fulfillment->delivered_at ? 'success' : 'neutral'" dot="full">{{ $fulfillment->delivered_at ? __('admin.fulfill.delivered_badge') : __('admin.fulfill.fulfilled_badge') }}</x-admin.badge>
                        <span class="text-[13px] font-semibold">#{{ $fulfillment->sequence }}</span>
                        <span class="text-[13px] text-muted">{{ $fulfillment->created_at->timezone($tz)->translatedFormat('j M, H:i') }}</span>
                    </header>
                    @if ($fulfillment->carrier || $fulfillment->tracking_number)
                        <p class="px-4 pt-2 text-[13px] text-ink-soft">{{ $fulfillment->carrier }} @if ($fulfillment->tracking_number)· <span dir="ltr" class="font-medium text-ink-strong">{{ $fulfillment->tracking_number }}</span>@endif</p>
                    @endif
                    @include('admin.sales.orders.partials.lines', ['lines' => $fulfillment->items->map(fn ($fi) => [$fi->orderItem, $fi->quantity])])
                    @unless ($fulfillment->delivered_at || $order->isCancelled())
                        <form method="POST" action="{{ route('admin.fulfillments.delivered', $fulfillment) }}" class="flex justify-end border-t border-line px-4 py-3">
                            @csrf
                            <x-admin.button variant="secondary" icon="circle-check">{{ __('admin.orders.mark_delivered') }}</x-admin.button>
                        </form>
                    @endunless
                </x-admin.card>
            @endforeach

            @if ($order->isCancelled() && $order->fulfillments->isEmpty())
                <x-admin.card :padding="false">
                    <header class="flex items-center gap-2 px-4 pt-4"><x-admin.badge>{{ __('admin.orders.cancelled') }}</x-admin.badge></header>
                    @include('admin.sales.orders.partials.lines', ['lines' => $order->items->map(fn ($i) => [$i, $i->quantity])])
                </x-admin.card>
            @endif

            {{-- Payment --}}
            <x-admin.card data-tour="order-payment" :padding="false">
                <header class="flex items-center gap-2 px-4 pt-4">
                    <x-admin.badge :tone="$payTone" :dot="$payDot">{{ $order->payment_status->label() }}</x-admin.badge>
                    <span class="text-[13px] text-muted">{{ __("admin.orders.methods.{$order->payment_method}") }}@if ($order->display_currency !== $order->currency) · {{ __('admin.orders.browsed_in', ['currency' => $order->display_currency]) }}@endif</span>
                </header>
                <div class="px-4 py-3">@include('admin.sales.orders.partials.totals')</div>
                <dl class="space-y-1.5 border-t border-line px-4 py-3 text-sm">
                    <div class="flex justify-between"><dt>{{ $moneyIn ? __('admin.orders.paid_by_customer') : __('admin.orders.to_collect') }}</dt><dd dir="ltr">{{ price_admin($order->grand_total) }}</dd></div>
                    @foreach ($order->refunds as $refund)
                        <div class="flex justify-between text-ink-soft"><dt>{{ __('admin.refund.line', ['date' => $refund->created_at->timezone($tz)->translatedFormat('j M')]) }}@if ($refund->reason) <span class="text-muted">· {{ $refund->reason }}</span>@endif</dt><dd dir="ltr">−{{ price_admin($refund->amount) }}</dd></div>
                    @endforeach
                    @unless ($order->refunded_total->isZero())
                        <div class="flex justify-between font-semibold"><dt>{{ __('admin.orders.net_payment') }}</dt><dd dir="ltr">{{ price_admin($order->grand_total->subtract($order->refunded_total)) }}</dd></div>
                    @endunless
                </dl>
                @if ($canMarkPaid || $canRefund)
                    <div class="flex justify-end gap-2 border-t border-line px-4 py-3">
                        @if ($canRefund)<x-admin.button variant="secondary" :href="route('admin.orders.refunds.create', $order)">{{ __('admin.refund.title') }}</x-admin.button>@endif
                        @if ($canMarkPaid)
                            <form method="POST" action="{{ route('admin.orders.paid', $order) }}">@csrf<x-admin.button>{{ __('admin.orders.mark_paid') }}</x-admin.button></form>
                        @endif
                    </div>
                @endif
            </x-admin.card>

            {{-- Timeline --}}
            <div data-tour="order-timeline">
                <h2 class="mb-2 px-1 text-sm font-semibold text-ink-strong">{{ __('admin.orders.timeline') }}</h2>
                <form method="POST" action="{{ route('admin.orders.note', $order) }}" class="flex items-center gap-2 rounded-xl bg-surface p-2 shadow-card">
                    @csrf
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#b4c7ff] text-xs font-semibold">{{ auth('admin')->user()->initials() }}</span>
                    <label for="note" class="sr-only">{{ __('admin.orders.add_note') }}</label>
                    <input id="note" name="note" maxlength="1000" required placeholder="{{ __('admin.orders.comment_placeholder') }}" class="h-8 flex-1 border-0 bg-transparent text-sm outline-none">
                    <x-admin.button variant="secondary" size="sm">{{ __('admin.orders.post') }}</x-admin.button>
                </form>
                <ol class="relative ms-4 mt-4 space-y-4 border-s-2 border-line ps-6">
                    @foreach ($order->events as $event)
                        <li class="relative">
                            <span class="absolute -start-[1.95rem] top-1 size-2.5 rounded-full border-2 border-canvas bg-muted"></span>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                <p class="text-[13px]">
                                    @if ($event->type === 'note')<span class="font-medium">{{ $event->admin?->name }}</span>: {{ $event->message }}
                                    @else{{ __("admin.orders.events.{$event->type}") }}@if ($event->message && $event->message !== __("admin.orders.events.{$event->type}", [], 'en'))<span class="text-ink-soft"> — {{ $event->message }}</span>@endif @endif
                                </p>
                                {{-- "Order placed" always shows the order's own time, so it matches the header above. --}}
                                @php $at = $event->type === 'placed' && $order->placed_at ? $order->placed_at : $event->created_at; @endphp
                                <time class="text-xs text-muted" datetime="{{ $at->toIso8601String() }}">{{ $at->timezone($tz)->translatedFormat('j M, H:i') }}</time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="min-w-0 space-y-4">
            @if ($order->customer_note)
                <x-admin.card :title="__('admin.orders.notes')">
                    <p class="text-[13px] whitespace-pre-line text-ink-soft">{{ $order->customer_note }}</p>
                </x-admin.card>
            @endif

            <x-admin.card data-tour="order-customer" :padding="false">
                <div class="px-4 pt-4">
                    <h2 class="text-sm font-semibold text-ink-strong">{{ __('admin.orders.customer') }}</h2>
                    <p class="mt-2 text-[13px]">
                        @if ($order->user)<a href="{{ route('admin.customers.show', $order->user) }}" class="font-medium text-brand-600 hover:underline">{{ $order->ship_name }}</a>@else<span class="font-medium">{{ $order->ship_name }}</span>@endif
                    </p>
                    <p class="text-[13px] text-muted">{{ trans_choice('admin.customers.orders', $customerOrders, ['count' => $customerOrders]) }}@unless ($order->user) · {{ __('admin.orders.guest') }}@endunless</p>
                </div>
                <div class="mt-3 border-t border-line px-4 py-3">
                    <h3 class="text-[13px] font-semibold">{{ __('admin.orders.contact') }}</h3>
                    <p class="mt-1 text-[13px]"><a href="mailto:{{ $order->email }}" class="text-brand-600 hover:underline">{{ $order->email }}</a></p>
                    @if ($order->phone)<p class="text-[13px]"><a href="tel:{{ $order->phone }}" class="text-brand-600 hover:underline" dir="ltr">{{ $order->phone }}</a></p>@endif
                    <p class="mt-1 text-xs text-muted">{{ $order->marketing_opt_in ? __('admin.orders.opted_in') : __('admin.orders.not_opted_in') }}</p>
                </div>
                <div class="border-t border-line px-4 py-3">
                    <h3 class="text-[13px] font-semibold">{{ __('admin.orders.shipping_address') }}</h3>
                    <address class="mt-1 text-[13px] leading-5 not-italic">
                        {{ $order->ship_name }}<br>
                        {{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>
                        {{ $order->shipRegionLine() }}<br>
                        {{ __('store.checkout.uae') }}
                    </address>
                    <a href="https://maps.google.com/?q={{ urlencode($order->ship_address_line.', '.\App\Support\Countries::line($order->ship_city, $order->ship_state, $order->ship_country, $order->ship_emirate).', UAE') }}" target="_blank" rel="noopener" class="mt-1 inline-block text-[13px] text-brand-600 hover:underline">{{ __('admin.orders.view_map') }}</a>
                </div>
                <div class="border-t border-line px-4 py-3">
                    <h3 class="text-[13px] font-semibold">{{ __('admin.orders.billing_address') }}</h3>
                    <p class="mt-1 text-[13px] text-muted">{{ __('admin.orders.same_as_shipping') }}</p>
                </div>
            </x-admin.card>

            @if ($order->returns->isNotEmpty())
                <x-admin.card :title="__('admin.returns.title')">
                    <ul class="space-y-2 text-sm">
                        @foreach ($order->returns as $r)
                            <li class="flex items-center justify-between gap-3"><a href="{{ route('admin.returns.show', $r) }}" class="font-medium text-brand-600 hover:underline" dir="ltr">{{ $r->number }}</a><x-admin.badge :tone="$r->tone()">{{ __("admin.returns.status.{$r->status}") }}</x-admin.badge></li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>
    </div>
@endsection
