@extends('storefront.layouts.app')

{{-- My details — in the design's account shell. --}}
@section('furnivo', true)
@section('title', __('store.account.details'))
@section('noindex', true)

@php
    $eyebrow = 'font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 4px;';
    $value = 'font-size: 16px; color: rgb(32, 32, 32);';
    $recent = $customer->orders()->with('items')->latest('id')->limit(3)->get();
@endphp

@section('content')
    <x-store.account-shell>
        <h2 style="margin: 0px 0px 22px; font-size: clamp(20px, 2.4vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.account.details') }}</h2>

        <div style="border: 1px solid rgb(236, 236, 236); padding: clamp(22px, 3vw, 30px); display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 22px; margin-bottom: 28px;">
            <div><div style="{{ $eyebrow }}">{{ __('store.auth.full_name') }}</div><div style="{{ $value }}">{{ $customer->name }}</div></div>
            <div><div style="{{ $eyebrow }}">{{ __('store.auth.email') }}</div><div style="{{ $value }} overflow-wrap: anywhere;">{{ $customer->email }}</div></div>
            <div><div style="{{ $eyebrow }}">{{ __('store.auth.phone') }}</div><div style="{{ $value }}" dir="ltr">{{ $customer->phone ?: '—' }}</div></div>
        </div>

        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 18px;">
            <h3 style="margin: 0px; font-size: 18px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.account.orders') }}</h3>
            <a href="{{ store_route('account.orders') }}" style="font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29);">{{ __('store.account.all_orders') }} →</a>
        </div>

        @if ($recent->isEmpty())
            <p style="margin: 0px; font-size: 15px; color: rgb(102, 102, 102);">{{ __('store.account.no_orders_text') }}</p>
        @else
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($recent as $order)
                    <a href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; border: 1px solid rgb(236, 236, 236); padding: 16px 20px; color: inherit;">
                        <span style="display: flex; gap: 28px; flex-wrap: wrap;">
                            <span><span style="display: block; {{ $eyebrow }}">{{ __('store.order.number_short') }}</span><span style="display: block; font-size: 14px;">{{ $order->number }}</span></span>
                            <span><span style="display: block; {{ $eyebrow }}">{{ __('store.order.placed') }}</span><span style="display: block; font-size: 14px;">{{ $order->placed_at->translatedFormat('j M Y') }}</span></span>
                            <span><span style="display: block; {{ $eyebrow }}">{{ __('store.cart.total') }}</span><span style="display: block; font-size: 14px;">{{ price_order($order->grand_total, $order) }}</span></span>
                        </span>
                        <span style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(102, 102, 102);">{{ __("store.order.stages.{$order->stage()}") }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-store.account-shell>
@endsection
