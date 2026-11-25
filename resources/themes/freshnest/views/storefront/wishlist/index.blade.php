@php $inAccount = auth('web')->check() && ! $shared; @endphp
@extends($inAccount ? 'storefront.account.layout' : 'storefront.layouts.app')

{{-- Wishlist: inside the account (signed in) or on its own page (guests and shared lists),
     with the design's product cards. --}}
@section('freshnest', true)
@section('title', __('Wishlist'))
@section('noindex', true)
@section('account_tab', 'wishlist')

@php
    $home = app(\App\Themes\Freshnest\HomeData::class);
    $products->loadMissing(['variants.optionValues', 'categories', 'media']);
@endphp

@section($inAccount ? 'account' : 'content')
  @unless ($inAccount)
    @include('storefront.partials.page-band', ['title' => $shared ? __('A shared wishlist') : __('Wishlist'), 'blurb' => $shared ? __('Someone shared these favourites with you.') : 'Your saved favourites, ready when you are.'])
    <div class="wrap" style="padding-top:clamp(24px,3vw,40px); padding-bottom:clamp(40px,5vw,64px);">
  @endunless
      @if ($products->isEmpty())
        <div style="padding:40px; text-align:center; background:#FFFFFF; border:1px dashed #D7E3D8; border-radius:20px; color:var(--fn-muted);">
          {{ __('No saved items yet. Tap the heart on any product to keep it here.') }} <a href="{{ store_route('shop') }}" style="font-weight:700;">{{ __('Browse the shop') }}</a>
        </div>
      @else
        <div class="wgrid pgrid">
          @foreach ($products as $product)
            @include('storefront.collections.card', ['c' => $home->card($product)])
          @endforeach
        </div>
      @endif
  @unless ($inAccount)
    </div>
  @endunless
@endsection
