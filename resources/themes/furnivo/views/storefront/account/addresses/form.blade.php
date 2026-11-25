@extends('storefront.layouts.app')

{{-- Add or edit a saved address — in the design's account shell. --}}
@section('furnivo', true)
@section('title', $address->exists ? __('store.addresses.edit_title') : __('store.addresses.add'))
@section('noindex', true)

@php
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $field = fn ($name, $fallback = null) => old($name, $address->{$name} ?? $fallback);
@endphp

@section('content')
    <x-store.account-shell>
        <h2 style="margin: 0px 0px 22px; font-size: clamp(20px, 2.4vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ $address->exists ? __('store.addresses.edit_title') : __('store.addresses.add') }}</h2>

        <form method="POST" action="{{ $address->exists ? store_route('account.addresses.update', ['address' => $address->id]) : store_route('account.addresses.store') }}"
              class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; max-width: 720px;">
            @csrf
            @if ($address->exists) @method('PUT') @endif

            <label style="{{ $label }}">{{ __('store.addresses.label') }}
                <input name="label" value="{{ $field('label') }}" maxlength="40" placeholder="{{ __('store.addresses.label_placeholder') }}" style="{{ $input }}">
            </label>
            <label style="{{ $label }}">{{ __('store.checkout.full_name') }}
                <input name="name" value="{{ $field('name', auth('web')->user()->name) }}" required maxlength="120" autocomplete="name" style="{{ $input }}">
            </label>
            <label style="{{ $label }}">{{ __('store.auth.phone') }}
                <input name="phone" value="{{ $field('phone', auth('web')->user()->phone) }}" required dir="ltr" autocomplete="tel" style="{{ $input }}">
            </label>
            <label style="{{ $label }}"><span>{{ __('store.checkout.city') }}<x-store.required /></span>
                <input name="city" value="{{ $field('city') }}" required autocomplete="address-level2" style="{{ $input }}">
            </label>
            <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.checkout.address_line') }}<x-store.required /></span>
                <input name="address_line" value="{{ $field('address_line') }}" required autocomplete="address-line1" placeholder="{{ __('store.checkout.address_placeholder') }}" style="{{ $input }}">
            </label>
            <label style="{{ $label }}">{{ __('store.checkout.building') }}
                <input name="building" value="{{ $field('building') }}" autocomplete="address-line2" placeholder="{{ __('store.checkout.building_placeholder') }}" style="{{ $input }}">
            </label>
            <label style="{{ $label }}"><span>{{ __('store.checkout.emirate') }}<x-store.required /></span>
                <select name="emirate" required autocomplete="address-level1" style="{{ $input }}">
                    @foreach (config('store.emirates') as $emirate)
                        <option value="{{ $emirate }}" @selected($field('emirate') === $emirate)>{{ __("store.emirates.{$emirate}") }}</option>
                    @endforeach
                </select>
            </label>

            @foreach (['label', 'name', 'phone', 'city', 'address_line', 'building', 'emirate'] as $name)
                @error($name)<p role="alert" style="grid-column: 1 / -1; margin: 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
            @endforeach

            <label style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; font-size: 14px; color: rgb(102, 102, 102); cursor: pointer;">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default)) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                {{ __('store.addresses.use_as_default') }}
            </label>

            <div style="grid-column: 1 / -1; display: flex; gap: 12px; flex-wrap: wrap;">
                <button style="height: 52px; padding: 0px 32px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.addresses.save') }}</button>
                <a href="{{ store_route('account.addresses') }}" style="display: inline-flex; align-items: center; height: 52px; padding: 0px 26px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.addresses.cancel') }}</a>
            </div>
        </form>
    </x-store.account-shell>
@endsection
