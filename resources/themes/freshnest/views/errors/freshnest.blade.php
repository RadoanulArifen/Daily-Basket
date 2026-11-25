{{-- The storefront error page (404, 403, 419, 429, 500, 503) in the FreshNest design. --}}
@extends('storefront.layouts.app')

@section('freshnest', true)
@section('title', __("store.errors.{$code}.title"))
@section('noindex', true)

@section('content')
  <section style="background:#E8F5E9; border-bottom:1px solid #DCEEDF;">
    <div class="wrap" style="padding-top:clamp(48px,7vw,96px); padding-bottom:clamp(48px,7vw,96px); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:clamp(28px,4vw,56px);">
      <div style="flex:1 1 420px; min-width:0; max-width:620px;">
        <span style="display:inline-flex; align-items:center; gap:8px; background:#FFFFFF; color:#1B5E20; font-size:12.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:7px 13px; border-radius:999px;">
          <span style="width:7px; height:7px; border-radius:50%; background:#FF7A1A;"></span>{{ __('Error') }} {{ $code }}
        </span>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(32px,5vw,56px); line-height:1.06; letter-spacing:-.03em; margin:18px 0 0; color:#1F2A1F;">{{ __("store.errors.{$code}.title") }}</h1>
        <p style="margin:16px 0 0; font-size:16.5px; line-height:1.65; color:#6B7A6B; max-width:46ch;">{{ __("store.errors.{$code}.text") }}</p>

        <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:28px;">
          <a class="fh-ed87d8" href="{{ store_route('home') }}" style="display:inline-flex; align-items:center; gap:12px; padding:6px 6px 6px 22px; border-radius:999px; background:#2E9E4F; color:#FFFFFF; font-weight:700; font-size:15px;">
            <span>{{ __('Back to home') }}</span>
            <span style="display:inline-flex; width:34px; height:34px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2E9E4F" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
          </a>
          <a class="fh-d109e0" href="{{ store_route('shop') }}" style="display:inline-flex; align-items:center; padding:0 22px; min-height:46px; border-radius:999px; border:1.5px solid #CFE6D3; background:#FFFFFF; color:#1B5E20; font-weight:700; font-size:15px;">{{ __('Shop all products') }}</a>
        </div>

        @if ((string) $code === '404')
          <form method="GET" action="{{ store_route('search') }}" role="search" style="display:flex; align-items:center; gap:8px; margin-top:28px; max-width:520px; background:#FFFFFF; border:1.5px solid #DCEEDF; border-radius:999px; padding:6px;">
            <label for="error-search" class="fn-sr">{{ __('Search for product...') }}</label>
            <input id="error-search" name="q" type="search" placeholder="{{ __('Search for product...') }}" style="flex:1 1 auto; min-width:0; border:0; outline:none; background:transparent; font-size:15px; padding:10px 14px; color:#1F2A1F;">
            <button type="submit" style="border:0; background:#FF7A1A; color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px; padding:11px 20px; cursor:pointer;">{{ __('Search') }}</button>
          </form>
        @endif
      </div>

      <div aria-hidden="true" style="flex:0 1 360px; display:flex; justify-content:center;">
        <div style="position:relative; width:min(340px, 80vw); aspect-ratio:1/1; border-radius:50%; background:#FFFFFF; box-shadow:0 30px 70px rgba(27,94,32,.12); display:flex; align-items:center; justify-content:center;">
          <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(84px,12vw,128px); letter-spacing:-.05em; color:#2E9E4F; line-height:1;">{{ $code }}</span>
          <span style="position:absolute; top:14%; right:10%; display:inline-flex; width:58px; height:58px; border-radius:18px; background:#FF7A1A; align-items:center; justify-content:center; transform:rotate(12deg);">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="#FFFFFF"/><path d="M3 21C8 16 13 11 21 3" stroke="#FF7A1A" stroke-width="1.6" stroke-linecap="round"/></svg>
          </span>
          <span style="position:absolute; bottom:12%; left:8%; background:#1B5E20; color:#FFFFFF; font-size:12.5px; font-weight:700; padding:8px 14px; border-radius:999px;">{{ __('Fresh picks are one tap away') }}</span>
        </div>
      </div>
    </div>
  </section>
@endsection
