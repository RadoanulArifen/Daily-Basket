@extends('storefront.layouts.app')

{{-- Wishlist — markup and styles from the design. Share links carry the pieces
     (?items=…), so whoever opens the link sees the same list. --}}
@section('furnivo', true)
@section('title', __('store.wishlist.title'))
@section('noindex', true)

@php
    $shareUrl = $shareUrl ?? store_route('wishlist');
    $lines = $products->map(fn ($product) => '• '.$product->name)->implode("\n");
    $message = __('store.wishlist.share_message', ['store' => settings()->trans('general.store_name')])."\n".$lines."\n\n".$shareUrl;
    $iconButton = 'width: 38px; height: 38px; box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; display: flex; align-items: center; justify-content: center; color: rgb(32, 32, 32); transition: box-shadow 200ms, color 200ms;';
@endphp

@section('content')
    <section style="width: 100%; padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.wishlist.title') }}</span>
        </nav>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 34px;">
            <h1 style="margin: 0px; font-size: clamp(32px, 4vw, 48px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.wishlist.title') }}</h1>

            @if ($products->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 10px;" x-data="{ copied: false,
                        async copy() { try { await navigator.clipboard.writeText(@js($shareUrl)); this.copied = true; setTimeout(() => this.copied = false, 1800); } catch (e) {} } }">
                    <span style="font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(139, 135, 127); margin-right: 2px;">{{ __('store.wishlist.share') }}</span>
                    <a href="https://wa.me/?text={{ rawurlencode($message) }}" target="_blank" rel="noopener" title="{{ __('store.wishlist.share_whatsapp') }}" class="scpa" style="{{ $iconButton }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.8-.6-3-1.3-5-4.4-5.1-4.6-.2-.2-1.3-1.7-1.3-3.2s.8-2.3 1.1-2.6c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .7.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.2.4-.2.6-.1l2 1c.3.1.5.2.5.4.1.1.1.7-.2 1.4z"></path></svg>
                    </a>
                    <a href="mailto:?subject={{ rawurlencode(__('store.wishlist.share_subject', ['store' => settings()->trans('general.store_name')])) }}&body={{ rawurlencode($message) }}" title="{{ __('store.wishlist.share_email') }}" class="scpa" style="{{ $iconButton }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 6l9 7 9-7"></path></svg>
                    </a>
                    <button type="button" @click="copy()" class="scpa" style="height: 38px; padding: 0px 16px; box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; background: rgb(255, 255, 255); display: flex; align-items: center; gap: 8px; font-size: 12px; letter-spacing: 0.06em; color: rgb(32, 32, 32); cursor: pointer; transition: box-shadow 200ms;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"></path><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"></path></svg>
                        <span x-text="copied ? @js(__('store.wishlist.copied')) : @js(__('store.wishlist.copy_link'))">{{ __('store.wishlist.copy_link') }}</span>
                    </button>
                </div>
            @endif
        </div>

        @if ($shared ?? false)
            <p style="margin: -14px 0px 28px; font-size: 14px; color: rgb(139, 135, 127);">{{ __('store.wishlist.shared_note') }}</p>
        @endif

        @if ($products->isEmpty())
            <p style="margin: 0px 0px 24px; font-size: 16px; color: rgb(139, 135, 127);">{{ __('store.wishlist.empty') }}</p>
            <a href="{{ store_route('shop') }}" class="scpb" style="display: inline-flex; align-items: center; justify-content: center; height: 54px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; transition: opacity 250ms;">{{ __('store.wishlist.browse') }}</a>
        @else
            <div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(24px, 3vw, 44px) 28px;">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
