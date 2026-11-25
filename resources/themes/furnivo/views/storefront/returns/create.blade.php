@extends('storefront.layouts.app')

{{-- Request a return — the design's forms, on the store's own return rules. --}}
@section('furnivo', true)
@section('title', __('store.returns.heading'))
@section('noindex', true)

@php
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $card = 'cursor: pointer; display: flex; align-items: flex-start; gap: 13px; padding: 16px; transition: box-shadow 220ms;';
@endphp

@section('content')
    <section style="width: 100%; padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <a href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" style="cursor: pointer; color: inherit;">{{ __('store.order.title', ['number' => $order->number]) }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.returns.heading') }}</span>
        </nav>

        <h1 style="margin: 0px 0px 10px; font-size: clamp(30px, 3.6vw, 44px); line-height: 1.04; font-weight: 800; letter-spacing: -0.03em;">{{ __('store.returns.heading') }}</h1>
        <p style="margin: 0px 0px 34px; max-width: 60ch; font-size: 16px; line-height: 26px; color: rgb(102, 102, 102);">{{ __('store.returns.intro', ['days' => $days]) }}</p>

        <form method="POST" action="{{ store_route('returns.store', ['token' => $order->access_token]) }}" style="max-width: 760px;">
            @csrf

            <fieldset style="border: none; margin: 0px 0px 36px; padding: 0px;">
                <legend style="padding: 0px; margin-bottom: 16px; font-size: 18px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.returns.which') }}</legend>
                <div style="border-top: 1px solid rgb(236, 236, 236);">
                    @foreach ($order->items as $item)
                        @php $max = $returnable[$item->id] ?? 0; @endphp
                        <div style="display: flex; align-items: center; gap: 16px; padding: 18px 0px; border-bottom: 1px solid rgb(236, 236, 236);">
                            <span style="width: 64px; height: 64px; overflow: hidden; background: rgb(239, 236, 231); flex-shrink: 0; display: block;">
                                @if ($item->image)<img src="{{ $item->image }}" alt="" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                            </span>
                            <span style="flex: 1 1 0%; min-width: 0px;">
                                <span style="display: block; font-size: 15px; color: rgb(32, 32, 32);">{{ $item->name }}</span>
                                <span style="display: block; font-size: 13px; color: rgb(139, 135, 127);">@if ($item->variant_title){{ $item->variant_title }} · @endif{{ __('store.cart.quantity_short', ['count' => $item->quantity]) }}</span>
                            </span>
                            @if ($max > 0)
                                <label for="item-{{ $item->id }}" style="{{ $label }}">{{ __('store.cart.quantity_short', ['count' => '']) }}
                                    <select id="item-{{ $item->id }}" name="items[{{ $item->id }}]" style="{{ $input }} padding: 10px 12px;">
                                        @for ($q = 0; $q <= $max; $q++)
                                            <option value="{{ $q }}" @selected((int) old("items.{$item->id}") === $q)>{{ $q }}</option>
                                        @endfor
                                    </select>
                                </label>
                            @else
                                <span style="font-size: 13px; color: rgb(139, 135, 127);">{{ __('store.returns.already_requested') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @error('items')<p role="alert" style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset style="border: none; margin: 0px 0px 36px; padding: 0px;" x-data="{ resolution: @js(old('resolution', 'refund')) }">
                <legend style="padding: 0px; margin-bottom: 16px; font-size: 18px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.returns.resolution') }}<x-store.required /></legend>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 10px;">
                    @foreach (['refund', 'exchange'] as $option)
                        <label style="{{ $card }}" :style="resolution === @js($option) ? { boxShadow: 'rgb(29, 29, 29) 0px 0px 0px 2px inset' } : { boxShadow: 'rgb(217, 213, 207) 0px 0px 0px 1px inset' }">
                            <input type="radio" name="resolution" value="{{ $option }}" x-model="resolution" style="width: 16px; height: 16px; margin-top: 2px; accent-color: rgb(29, 29, 29);">
                            <span>
                                <span style="display: block; font-size: 15px; color: rgb(32, 32, 32);">{{ __("store.returns.resolutions.{$option}") }}</span>
                                <span style="display: block; font-size: 13px; line-height: 20px; color: rgb(139, 135, 127);">{{ __("store.returns.resolutions_help.{$option}") }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('resolution')<p role="alert" style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
            </fieldset>

            <div class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; margin-bottom: 30px;">
                <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.returns.reason') }}<x-store.required /></span>
                    <select name="reason" required style="{{ $input }}">
                        @foreach (\App\Models\ReturnRequest::REASONS as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ __("store.returns.reasons.{$reason}") }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="{{ $label }} grid-column: 1 / -1;">{{ __('store.returns.note') }}
                    <textarea name="note" rows="4" maxlength="1000" placeholder="{{ __('store.returns.note') }}" style="{{ $input }} resize: vertical;">{{ old('note') }}</textarea>
                </label>
                @foreach (['reason', 'note'] as $field)
                    @error($field)<p role="alert" style="grid-column: 1 / -1; margin: 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
                @endforeach
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button style="height: 52px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.returns.submit') }}</button>
                <a href="{{ store_route('orders.show', ['token' => $order->access_token]) }}" style="display: inline-flex; align-items: center; height: 52px; padding: 0px 26px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.addresses.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
