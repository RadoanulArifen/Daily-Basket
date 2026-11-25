@extends('storefront.layouts.app')

{{-- Order page — the design's confirmation screen, used both right after checkout and
     whenever the customer opens the order again from their email or account. --}}
@section('furnivo', true)
@section('title', __('store.order.title', ['number' => $order->number]))
@section('noindex', true)

@php
    $stage = $order->stage();
    $steps = ['confirmed' => __('store.order.stages.confirmed'), 'shipped' => __('store.order.stages.shipped'), 'delivered' => __('store.order.stages.delivered')];
    $reached = array_search($stage, array_keys($steps), true);
    $green = 'rgb(76, 175, 125)';
    $placed = session('placed') && $stage !== 'awaiting_payment' && $stage !== 'cancelled';
    $eyebrow = 'font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 4px;';
    $value = 'font-size: 14px; color: rgb(32, 32, 32);';
    $button = 'height: 52px; padding: 0px 30px; font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; display: flex; align-items: center; justify-content: center; cursor: pointer;';
@endphp

@if (session('placed') && settings()->get('tracking.ga_measurement_id') && ! $order->awaitsOnlinePayment())
    @include('storefront.orders.purchase-event', ['order' => $order])
@endif

@section('content')
    <section style="max-width: 920px; margin: 0px auto; padding: clamp(18px, 2vw, 28px) clamp(20px, 4vw, 40px) clamp(44px, 6vw, 72px);">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px;">
            @if ($placed)
                <div aria-hidden="true" style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 50%; background: {{ $green }}; color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 20px;">✓</div>
            @endif
            <div>
                <h1 style="margin: 0px 0px 2px; font-size: clamp(22px, 2.6vw, 30px); font-weight: 800; letter-spacing: -0.02em;">{{ $placed ? __('store.order.confirmed_title') : __('store.order.title', ['number' => $order->number]) }}</h1>
                <p style="margin: 0px; font-size: 14px; line-height: 20px; color: rgb(139, 135, 127);">
                    @if ($placed)
                        Order <span>{{ $order->number }}</span> · {{ __('store.order.thanks_text', ['email' => $order->email]) }}
                    @else
                        {{ $order->placed_at->timezone(config('store.display_timezone'))->translatedFormat('j F Y') }}
                    @endif
                </p>
            </div>
        </div>

        @if ($stage === 'cancelled')
            <p role="status" style="margin: 0px 0px 24px; padding: 16px 18px; border: 1px solid rgb(236, 236, 236); background: rgb(250, 249, 247); font-size: 15px;">{{ __('store.order.stages.cancelled') }}</p>
        @elseif ($order->awaitsOnlinePayment())
            <div style="margin: 0px 0px 24px; padding: 20px; border: 1px solid rgb(199, 64, 52);">
                <p style="margin: 0px 0px 6px; font-size: 16px; color: rgb(32, 32, 32);">{{ __('store.order.awaiting_payment') }}</p>
                @if (session('payment_error'))<p style="margin: 0px 0px 12px; font-size: 14px; color: rgb(199, 64, 52);">{{ session('payment_error') }}</p>@endif
                <form method="POST" action="{{ store_route('orders.pay', ['token' => $order->access_token]) }}" style="margin: 12px 0px 0px;">
                    @csrf
                    <button style="{{ $button }} border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255);">{{ __('store.order.pay_now', ['total' => price($order->grand_total)]) }}</button>
                </form>
            </div>
        @endif

        <div class="two-col fx-split-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2px; border: 1px solid rgb(236, 236, 236); background: rgb(236, 236, 236);">
            <div style="background: rgb(255, 255, 255); padding: 22px; display: flex; flex-direction: column; min-width: 0px;">
                <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 24px;">
                    <div>
                        <div style="{{ $eyebrow }}">{{ __('store.checkout.payment') }}</div>
                        <div style="{{ $value }}"><span>{{ __("store.order.methods.{$order->payment_method}") }}</span></div>
                    </div>
                    <div>
                        <div style="{{ $eyebrow }}">{{ __('store.checkout.delivery') }}</div>
                        <div style="{{ $value }}"><span>{{ $order->shipping_method_name ?: __('store.checkout.delivery') }}</span></div>
                    </div>
                    <div>
                        <div style="{{ $eyebrow }}">{{ __('store.order.title', ['number' => '']) }}</div>
                        <div style="{{ $value }}"><span>{{ $order->number }}</span></div>
                    </div>
                </div>

                @if ($stage !== 'cancelled')
                    <div style="{{ $eyebrow }} margin-bottom: 18px;">{{ __('store.order.progress') }}</div>
                    <div class="fx-track-steps" style="display: flex; align-items: flex-start; margin-bottom: 6px;">
                        @foreach ($steps as $key => $text)
                            @php $done = $reached !== false && $loop->index <= $reached; @endphp
                            <div style="flex: 1 1 0%; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative;">
                                @unless ($loop->first)
                                    <div style="position: absolute; top: 11px; left: -50%; width: 100%; height: 2px; background: {{ $done ? $green : 'rgb(228, 225, 218)' }};"></div>
                                @endunless
                                <div style="position: relative; z-index: 2; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; {{ $done ? 'background: '.$green.'; box-shadow: '.$green.' 0px 0px 0px 1px inset; color: rgb(255, 255, 255);' : 'background: rgb(255, 255, 255); box-shadow: rgb(228, 225, 218) 0px 0px 0px 1px inset; color: rgb(139, 135, 127);' }}"><span>{{ $done ? '✓' : $loop->iteration }}</span></div>
                                <div style="margin-top: 8px; font-size: 11px; line-height: 14px; color: {{ $done ? 'rgb(32, 32, 32)' : 'rgb(139, 135, 127)' }}; max-width: 72px;"><span>{{ $text }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($order->tracking_number)
                    <p style="margin: 18px 0px 0px; font-size: 14px; color: rgb(32, 32, 32);">{{ __('store.order.tracking', ['carrier' => $order->carrier, 'number' => $order->tracking_number]) }}</p>
                @endif

                <div style="margin-top: auto; padding-top: 22px;">
                    <div style="{{ $eyebrow }}">{{ __('store.checkout.address') }}</div>
                    <p style="margin: 0px; font-size: 14px; line-height: 21px; color: rgb(102, 102, 102);">
                        {{ $order->ship_name }}<br>{{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>
                        {{ $order->shipRegionLine() }}<br>
                        <span dir="ltr">{{ $order->ship_phone }}</span>
                    </p>
                </div>
            </div>

            <div style="background: rgb(255, 255, 255); padding: 22px; min-width: 0px;">
                <div style="display: flex; flex-direction: column; gap: 14px; padding-bottom: 18px; border-bottom: 1px solid rgb(236, 236, 236);">
                    @foreach ($order->items as $item)
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div style="width: 52px; height: 52px; overflow: hidden; background: rgb(239, 236, 231); flex-shrink: 0;">
                                @if ($item->image)<img src="{{ $item->image }}" alt="" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                            </div>
                            <div style="flex: 1 1 0%; min-width: 0px; font-size: 14px;">
                                <div style="color: rgb(32, 32, 32);"><span>{{ $item->name }}</span></div>
                                <div style="color: rgb(102, 102, 102);">Qty <span>{{ $item->quantity }}</span>@if ($item->variant_title) · {{ $item->variant_title }}@endif</div>
                                @if ($item->optionsLabel())<div style="color: rgb(139, 135, 127); font-size: 12px; line-height: 1.45;">{{ $item->optionsLabel() }}</div>@endif
                            </div>
                            <div style="font-size: 14px; color: rgb(32, 32, 32);">{{ price($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="padding-top: 18px;">@include('storefront.orders.partials.totals', ['order' => $order])</div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 26px;">
            @auth('web')
                <a href="{{ store_route('account.orders') }}" style="{{ $button }} flex: 1 1 260px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255);">{{ __('store.account.all_orders') }}</a>
            @endauth
            <a href="{{ store_route('shop') }}" style="{{ $button }} flex: 1 1 260px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32);">{{ __('store.cart.continue') }}</a>
            <a href="{{ store_route('orders.invoice', ['token' => $order->access_token]) }}" target="_blank" rel="noopener" style="{{ $button }} flex: 0 1 auto; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32);">{{ __('store.order.invoice') }}</a>
        </div>

        @php
            $returnService = app(\App\Services\Returns\ReturnService::class);
            $orderReturns = $order->returns()->withSum('items as units', 'quantity')->latest('id')->get();
        @endphp
        @if ($orderReturns->isNotEmpty() || $returnService->canRequest($order))
            <section style="margin-top: 34px; border: 1px solid rgb(236, 236, 236); padding: 22px;" aria-labelledby="returns-heading">
                <h2 id="returns-heading" style="margin: 0px 0px 14px; font-size: 16px; letter-spacing: 0.02em;">{{ __('store.returns.section') }}</h2>
                @foreach ($orderReturns as $return)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0px; border-bottom: 1px solid rgb(241, 239, 236); font-size: 14px;">
                        <span style="color: rgb(102, 102, 102);">{{ __("store.returns.resolutions.{$return->resolution}") }} · {{ trans_choice('store.returns.units', $return->units ?? 0, ['count' => $return->units ?? 0]) }}</span>
                        <span style="background: rgb(245, 244, 240); padding: 4px 10px; font-size: 13px;">{{ __("store.returns.status.{$return->status}") }}</span>
                    </div>
                @endforeach
                @if ($returnService->canRequest($order))
                    <p style="margin: 14px 0px 0px; font-size: 14px; color: rgb(102, 102, 102);">{{ __('store.returns.until', ['date' => $order->delivered_at->addDays($returnService->windowDays())->translatedFormat('j F Y')]) }}</p>
                    <a href="{{ store_route('returns.create', ['token' => $order->access_token]) }}" style="{{ $button }} display: inline-flex; margin-top: 14px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32);">{{ __('store.returns.request') }}</a>
                @endif
            </section>
        @endif
    </section>
@endsection
