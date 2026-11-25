@extends('storefront.layouts.app')

{{-- After someone clicks "unsubscribe" in an email. --}}
@section('furnivo', true)
@section('title', __('store.reminder.unsubscribed_title'))
@section('noindex', true)

@section('content')
    <section style="width: 100%; padding: clamp(60px, 8vw, 110px) clamp(24px, 4.5vw, 80px); display: flex; flex-direction: column; align-items: center; text-align: center;">
        <div aria-hidden="true" style="width: 48px; height: 48px; margin-bottom: 22px; border-radius: 50%; background: rgb(76, 175, 125); color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 22px;">✓</div>
        <h1 style="margin: 0px; font-size: clamp(28px, 3.4vw, 44px); line-height: 1.08; font-weight: 800; letter-spacing: -0.03em; max-width: 20ch;">{{ __('store.reminder.unsubscribed_title') }}</h1>
        <p style="margin: 20px 0px 0px; max-width: 46ch; font-size: 17px; line-height: 28px; color: rgb(85, 85, 85);">{{ __('store.reminder.unsubscribed_text') }}</p>

        <a href="{{ store_route('shop') }}" class="scpb" style="margin-top: 34px; display: inline-flex; align-items: center; height: 54px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; transition: opacity 250ms;">{{ __('store.cart.continue') }}</a>
    </section>
@endsection
