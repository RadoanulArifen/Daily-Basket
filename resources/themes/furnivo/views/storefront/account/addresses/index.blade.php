@extends('storefront.layouts.app')

{{-- Saved addresses — in the design's account shell. --}}
@section('furnivo', true)
@section('title', __('store.addresses.title'))
@section('noindex', true)

@section('content')
    <x-store.account-shell>
        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px;">
            <h2 style="margin: 0px; font-size: clamp(20px, 2.4vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.addresses.title') }}</h2>
            <a href="{{ store_route('account.addresses.create') }}" style="display: inline-flex; align-items: center; height: 46px; padding: 0px 24px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;">{{ __('store.addresses.add') }}</a>
        </div>

        @if ($addresses->isEmpty())
            <p style="margin: 0px; font-size: 15px; color: rgb(102, 102, 102);">{{ __('store.account.no_addresses') }}</p>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                @foreach ($addresses as $address)
                    <div style="border: 1px solid {{ $address->is_default ? 'rgb(29, 29, 29)' : 'rgb(236, 236, 236)' }}; padding: 22px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <span style="font-size: 16px; font-weight: 600; color: rgb(32, 32, 32);">{{ $address->label ?: $address->name }}</span>
                            @if ($address->is_default)
                                <span style="font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; background: rgb(29, 29, 29); color: rgb(255, 255, 255); padding: 4px 10px;">{{ __('store.addresses.default') }}</span>
                            @endif
                        </div>
                        <p style="margin: 0px; font-size: 14px; line-height: 22px; color: rgb(102, 102, 102);">
                            @if ($address->label){{ $address->name }}<br>@endif
                            {{ $address->address_line }}@if ($address->building), {{ $address->building }}@endif<br>
                            {{ $address->regionLine() }}<br>
                            <span dir="ltr">{{ $address->phone }}</span>
                        </p>
                        <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-top: auto; padding-top: 8px; font-size: 13px;">
                            <a href="{{ store_route('account.addresses.edit', ['address' => $address->id]) }}" style="color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.addresses.edit') }}</a>
                            @unless ($address->is_default)
                                <form method="POST" action="{{ store_route('account.addresses.default', ['address' => $address->id]) }}" style="margin: 0px;">
                                    @csrf
                                    <button style="border: none; background: transparent; color: rgb(102, 102, 102); font-size: 13px; text-decoration: underline; cursor: pointer; padding: 0px;">{{ __('store.addresses.make_default') }}</button>
                                </form>
                                <form method="POST" action="{{ store_route('account.addresses.destroy', ['address' => $address->id]) }}" style="margin: 0px;" x-data @submit="if (! confirm(@js(__('store.addresses.delete_confirm')))) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <button style="border: none; background: transparent; color: rgb(102, 102, 102); font-size: 13px; text-decoration: underline; cursor: pointer; padding: 0px;">{{ __('store.addresses.delete') }}</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-store.account-shell>
@endsection
