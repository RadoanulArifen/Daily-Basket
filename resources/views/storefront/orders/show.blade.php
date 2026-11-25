@extends('storefront.layouts.app')

@section('title', __('store.order.title', ['number' => $order->number]))
@section('noindex', true)

@php
    $stage = $order->stage();
    $steps = ['confirmed', 'shipped', 'delivered'];
    $reached = array_search($stage, $steps, true);
@endphp

@if (session('placed') && settings()->get('tracking.ga_measurement_id') && ! $order->awaitsOnlinePayment())
    @include('storefront.orders.purchase-event', ['order' => $order])
@endif

@section('content')
    <div class="mx-auto max-w-4xl px-4 pb-20 pt-10 sm:px-6">
        @if (session('placed') && $stage !== 'awaiting_payment')
            <div class="mb-8 rounded-md bg-wool-soft p-6">
                <x-icon name="circle-check" class="size-8 text-success" />
                <h1 class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('store.order.thanks', ['name' => \Illuminate\Support\Str::before($order->ship_name, ' ')]) }}</h1>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.order.thanks_text', ['email' => $order->email]) }}</p>
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-stone">{{ $order->placed_at->timezone(config('store.display_timezone'))->translatedFormat('j F Y') }}</p>
                <h2 class="text-2xl font-semibold tracking-tight">{{ __('store.order.title', ['number' => $order->number]) }}</h2>
            </div>
            <a href="{{ store_route('orders.invoice', ['token' => $order->access_token]) }}" target="_blank" class="inline-flex items-center gap-2 text-[15px] underline decoration-ink/30 underline-offset-4 hover:decoration-ink">
                <x-icon name="receipt" class="size-4" /> {{ __('store.order.invoice') }}
            </a>
        </div>

        {{-- Status --}}
        @if ($stage === 'cancelled')
            <x-store.alert tone="error" class="mt-6">{{ __('store.order.cancelled') }}</x-store.alert>
        @elseif ($stage === 'awaiting_payment')
            <div class="mt-6 rounded-md border border-brand-red/30 bg-brand-red/5 p-5">
                <p class="font-medium">{{ request('payment') === 'cancelled' ? __('store.order.payment_cancelled') : __('store.order.awaiting_payment') }}</p>
                <p class="mt-1 text-[15px] text-ink-soft">{{ __('store.order.awaiting_payment_text', ['minutes' => config('store.unpaid_order_minutes')]) }}</p>
                @if (session('payment_error'))<p class="mt-2 text-[15px] text-danger">{{ session('payment_error') }}</p>@endif
                <form method="POST" action="{{ store_route('orders.pay', ['token' => $order->access_token]) }}" class="mt-4">
                    @csrf
                    <x-store.button>{{ __('store.order.pay_now', ['total' => price($order->grand_total)]) }}</x-store.button>
                </form>
            </div>
        @else
            <ol class="mt-8 grid grid-cols-3 gap-2" aria-label="{{ __('store.order.progress') }}">
                @foreach ($steps as $i => $step)
                    <li>
                        <span @class(['block h-1.5 rounded-full', 'bg-ink' => $reached !== false && $i <= $reached, 'bg-line' => $reached === false || $i > $reached])></span>
                        <span @class(['mt-2 block text-sm', 'font-medium' => $i === $reached, 'text-stone' => $reached === false || $i > $reached]) @if ($i === $reached) aria-current="step" @endif>{{ __("store.order.stages.{$step}") }}</span>
                    </li>
                @endforeach
            </ol>
            @if ($order->tracking_number)
                <p class="mt-4 text-[15px]">{{ __('store.order.tracking', ['carrier' => $order->carrier, 'number' => $order->tracking_number]) }}</p>
            @endif
        @endif

        <div class="mt-10 grid gap-10 md:grid-cols-5">
            <section class="md:col-span-3" aria-label="{{ __('store.order.items') }}">
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 py-4">
                            @if ($item->image)<img src="{{ $item->image }}" alt="" class="aspect-[4/5] w-16 rounded-sm bg-wool-soft object-cover">@else<span class="block aspect-[4/5] w-16 rounded-sm bg-wool-soft"></span>@endif
                            <span class="min-w-0 flex-1 text-[15px]">
                                <span class="block">{{ $item->name }}</span>
                                <span class="block text-sm text-ink-soft">@if ($item->variant_title){{ $item->variant_title }}@if ($item->optionsLabel()) · {{ $item->optionsLabel() }}@endif · @endif{{ __('store.order.qty', ['count' => $item->quantity]) }}</span>
                            </span>
                            <span class="text-[15px]">{{ price_order($item->line_total, $order) }}</span>
                        </li>
                    @endforeach
                </ul>
                @include('storefront.orders.partials.totals')
            </section>

            <aside class="space-y-8 text-[15px] md:col-span-2">
                <div>
                    <h3 class="font-medium">{{ __('store.checkout.address') }}</h3>
                    <address class="mt-2 not-italic leading-relaxed text-ink-soft">
                        {{ $order->ship_name }}<br>{{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>
                        {{ $order->shipRegionLine() }}<br>
                        <span dir="ltr">{{ $order->ship_phone }}</span>
                    </address>
                </div>
                <div>
                    <h3 class="font-medium">{{ __('store.checkout.payment') }}</h3>
                    <p class="mt-2 text-ink-soft">{{ __("store.order.methods.{$order->payment_method}") }} · {{ __("store.order.payment_status.{$order->payment_status->value}") }}</p>
                </div>
                <div>
                    <h3 class="font-medium">{{ __('store.order.help_title') }}</h3>
                    <p class="mt-2 text-ink-soft">{{ __('store.order.help_text', ['number' => $order->number]) }}
                        @if ($email = settings()->get('general.store_email'))<a href="mailto:{{ $email }}" class="text-ink underline underline-offset-4">{{ $email }}</a>@endif
                    </p>
                </div>
            </aside>
        </div>

        {{-- Returns and exchanges --}}
        @php $returnService = app(\App\Services\Returns\ReturnService::class); $orderReturns = $order->returns()->withSum('items as units', 'quantity')->get(); @endphp
        @if ($orderReturns->isNotEmpty() || $returnService->canRequest($order))
            <section class="mt-12 rounded-md border border-line p-6" aria-labelledby="returns-heading">
                <h3 id="returns-heading" class="font-medium">{{ __('store.returns.section') }}</h3>
                @foreach ($orderReturns as $r)
                    <p class="mt-3 flex flex-wrap items-center gap-x-3 text-[15px]">
                        <span class="font-medium" dir="ltr">{{ $r->number }}</span>
                        <span class="text-ink-soft">{{ __("store.returns.resolutions.{$r->resolution}") }} · {{ trans_choice('store.returns.units', (int) $r->units, ['count' => (int) $r->units]) }}</span>
                        <span class="rounded-full bg-wool-soft px-2.5 py-0.5 text-sm">{{ __("store.returns.status.{$r->status}") }}</span>
                    </p>
                @endforeach
                @if ($returnService->canRequest($order))
                    <p class="mt-3 text-[15px] text-ink-soft">{{ __('store.returns.until', ['date' => $order->delivered_at->addDays($returnService->windowDays())->timezone(config('store.display_timezone'))->translatedFormat('j F Y')]) }}</p>
                    <x-store.button :href="store_route('returns.create', ['token' => $order->access_token])" variant="secondary" class="mt-4">{{ __('store.returns.start') }}</x-store.button>
                @endif
            </section>
        @endif

        <div class="mt-12 flex flex-wrap gap-3">
            <x-store.button :href="store_route('shop')" variant="secondary">{{ __('store.cart.continue') }}</x-store.button>
            @auth('web')<x-store.button :href="store_route('account.orders')" variant="link">{{ __('store.account.all_orders') }}</x-store.button>@endauth
        </div>
    </div>
@endsection
