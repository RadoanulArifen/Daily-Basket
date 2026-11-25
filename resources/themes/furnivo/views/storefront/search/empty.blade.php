@extends('storefront.layouts.app')

{{-- Nothing found (or nothing searched for yet), in the design's style. --}}
@section('furnivo', true)
@section('title', __('store.search.title'))
@section('noindex', true)

@section('content')
    <section style="width: 100%; padding: clamp(60px, 8vw, 110px) clamp(24px, 4.5vw, 80px); display: flex; flex-direction: column; align-items: center; text-align: center;">
        <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 18px;"><span>{{ __('store.theme.search_results') }}</span></div>
        <h1 style="margin: 0px; font-size: clamp(30px, 4vw, 52px); line-height: 1.06; font-weight: 800; letter-spacing: -0.03em; max-width: 22ch;">
            {{ filled($term) ? __('store.search.no_results', ['term' => $term]) : __('store.search.title') }}
        </h1>
        <p style="margin: 22px 0px 0px; max-width: 48ch; font-size: 17px; line-height: 29px; color: rgb(85, 85, 85);">{{ __('store.search.no_results_text') }}</p>

        <form method="GET" action="{{ store_route('search') }}" role="search" style="display: flex; align-items: center; margin-top: 34px; width: min(520px, 100%); border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255);">
            <label for="search-again" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;">Search furniture, lighting, brands</label>
            <input id="search-again" name="q" type="search" value="{{ $term }}" placeholder="{{ __('store.theme.search_placeholder') }}" style="flex: 1 1 0%; min-width: 0px; height: 54px; border: none; background: transparent; padding: 0px 18px; font-size: 15px; color: rgb(32, 32, 32); outline: none;">
            <button type="submit" style="height: 54px; padding: 0px 24px; border: none; background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.search.submit') }}</button>
        </form>

        <div style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 32px;">
            <a href="{{ store_route('shop') }}" class="scpc" style="height: 52px; padding: 0px 30px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; display: flex; align-items: center; transition: background 250ms, color 250ms;">{{ __('store.theme.shop_all') }}</a>
        </div>
    </section>

    @php $picks = app(\App\Services\Catalog\StorefrontCatalog::class)->products()->with(['media', 'brandRecord', 'variants'])->inRandomOrder()->limit(8)->get(); @endphp
    @if ($picks->isNotEmpty())
        @include('storefront.products.rail', ['rail' => ['key' => 'search-picks', 'title' => 'Popular right now', 'products' => $picks, 'url' => store_route('shop')]])
    @endif
@endsection
