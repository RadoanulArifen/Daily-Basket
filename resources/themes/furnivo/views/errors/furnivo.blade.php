{{-- The storefront error page, in the Furnivo design. --}}
@extends('storefront.layouts.app')

@section('furnivo', true)
@section('title', __("store.errors.{$code}.title"))

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section style="width: 100%; padding: clamp(64px, 9vw, 120px) clamp(24px, 4.5vw, 80px); display: flex; flex-direction: column; align-items: center; text-align: center;">
        <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 18px;">Error {{ $code }}</div>
        <h1 style="margin: 0px; font-size: clamp(36px, 5.2vw, 68px); line-height: 1.06; font-weight: 800; letter-spacing: -0.03em; max-width: 20ch;">{{ __("store.errors.{$code}.title") }}</h1>
        <p style="margin: 24px 0px 0px; max-width: 46ch; font-size: 17px; line-height: 29px; color: rgb(85, 85, 85);">{{ __("store.errors.{$code}.text") }}</p>

        <div style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 40px;">
            <a href="{{ store_route('home') }}" class="scpb" style="height: 56px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; display: flex; align-items: center; justify-content: center; transition: opacity 250ms;">{{ __('store.errors.home') }}</a>
            <a href="{{ store_route('shop') }}" class="scpc" style="height: 56px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; display: flex; align-items: center; justify-content: center; transition: background 250ms, color 250ms;">{{ __('store.theme.shop_all') }}</a>
        </div>

        @if ($code === '404')
            <form method="GET" action="{{ store_route('search') }}" role="search" style="display: flex; align-items: center; gap: 0px; margin-top: 46px; width: min(520px, 100%); border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255);">
                <label for="error-search" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Search furniture, lighting, brands</label>
                <input id="error-search" name="q" type="search" placeholder="{{ __('store.theme.search_placeholder') }}" style="flex: 1 1 0%; height: 54px; border: none; background: transparent; padding: 0px 18px; font-size: 15px; color: rgb(32, 32, 32); outline: none;">
                <button type="submit" style="height: 54px; padding: 0px 24px; border: none; background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.search.submit') }}</button>
            </form>
        @endif
    </section>

    @if ($code === '404' && ($picks = app(\App\Services\Catalog\StorefrontCatalog::class)->products()->with(['media', 'brandRecord', 'variants'])->inRandomOrder()->limit(8)->get())->isNotEmpty())
        @include('storefront.products.rail', ['rail' => ['key' => 'error-picks', 'title' => 'Popular right now', 'products' => $picks, 'url' => store_route('shop')]])
    @endif
@endsection
