@extends('storefront.layouts.app')

{{-- Order history — the design's rows, expandable to show the pieces. --}}
@section('furnivo', true)
@section('title', __('store.account.orders'))
@section('noindex', true)

@php
    $eyebrow = 'font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 3px;';
    $value = 'font-size: 14px; color: rgb(32, 32, 32);';
    $tone = ['delivered' => 'rgb(76, 175, 125)', 'cancelled' => 'rgb(199, 64, 52)', 'shipped' => 'rgb(29, 29, 29)', 'confirmed' => 'rgb(29, 29, 29)', 'awaiting_payment' => 'rgb(199, 64, 52)'];
@endphp

@section('content')
    <x-store.account-shell :orders-count="$orders->total()">
        <h2 style="margin: 0px 0px 22px; font-size: clamp(20px, 2.4vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.account.orders') }}</h2>

        @if ($orders->isEmpty())
            <p style="margin: 0px 0px 10px; font-size: 16px; color: rgb(32, 32, 32);">{{ __('store.account.no_orders') }}</p>
            <p style="margin: 0px 0px 22px; font-size: 15px; color: rgb(102, 102, 102);">{{ __('store.account.no_orders_text') }}</p>
            <a href="{{ store_route('shop') }}" style="display: inline-flex; align-items: center; height: 52px; padding: 0px 30px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.wishlist.browse') }}</a>
        @else
            <div style="display: flex; flex-direction: column; gap: 16px;">
                @foreach ($orders as $order)
                    @php $stage = $order->stage(); @endphp
                    <div style="border: 1px solid rgb(236, 236, 236);" x-data="{ open: false }">
                        <button type="button" @click="open = ! open" :aria-expanded="open" style="width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 18px 22px; background: rgb(250, 249, 247); border: none; text-align: start;">
                            <span style="display: flex; gap: 28px; flex-wrap: wrap;">
                                <span><span style="display: block; {{ $eyebrow }}">{{ __('store.order.number_short') }}</span><span style="display: block; {{ $value }}">{{ $order->number }}</span></span>
                                <span><span style="display: block; {{ $eyebrow }}">{{ __('store.order.placed') }}</span><span style="display: block; {{ $value }}">{{ $order->placed_at->translatedFormat('j M Y') }}</span></span>
                                <span><span style="display: block; {{ $eyebrow }}">{{ __('store.cart.total') }}</span><span style="display: block; {{ $value }}">{{ price_order($order->grand_total, $order) }}</span></span>
                            </span>
                            <span style="display: flex; align-items: center; gap: 16px;">
                                <span style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; padding: 6px 12px; color: {{ $tone[$stage] ?? 'rgb(29, 29, 29)' }}; box-shadow: {{ $tone[$stage] ?? 'rgb(29, 29, 29)' }} 0px 0px 0px 1px inset;">{{ __("store.order.stages.{$stage}") }}</span>
                                <span aria-hidden="true" style="font-size: 13px; color: rgb(139, 135, 127); transition: transform 250ms;" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }">⌄</span>
                            </span>
                        </button>

                        <div x-show="open" x-cloak>
                            <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                                @foreach ($order->items as $item)
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span style="width: 52px; height: 52px; overflow: hidden; background: rgb(239, 236, 231); flex-shrink: 0; display: block;">
                                            @if ($item->image)<img src="{{ $item->image }}" alt="" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                                        </span>
                                        <span style="flex: 1 1 0%; min-width: 0px; font-size: 14px;">
                                            <span style="display: block; color: rgb(32, 32, 32);">{{ $item->name }}</span>
                                            <span style="display: block; color: rgb(102, 102, 102);">{{ __('store.cart.quantity_short', ['count' => $item->quantity]) }}@if ($item->optionsLabel()) · {{ $item->optionsLabel() }}@endif</span>
                                        </span>
                                        <span style="font-size: 14px; color: rgb(32, 32, 32);">{{ price_order($item->line_total, $order) }}</span>
                                    </div>
                                @endforeach
                                <div style="display: flex; gap: 12px; flex-wrap: wrap; padding-top: 6px;">
                                    <a href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" style="display: inline-flex; align-items: center; height: 46px; padding: 0px 24px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.order.view') }}</a>
                                    <a href="{{ store_route('orders.invoice', ['token' => $order->access_token]) }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; height: 46px; padding: 0px 24px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.order.invoice') }}</a>
                                    {{-- Straight back into the bag, at today's prices. --}}
                                    <form method="POST" action="{{ store_route('account.orders.reorder', ['order' => $order->id]) }}" style="display: inline-flex;">
                                        @csrf
                                        <button type="submit" style="display: inline-flex; align-items: center; height: 40px; padding: 0px 18px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; cursor: pointer;">{{ __('store.theme.reorder') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($orders->hasPages())
                <div style="margin-top: 32px;">{{ $orders->links() }}</div>
            @endif
        @endif
    </x-store.account-shell>
@endsection
