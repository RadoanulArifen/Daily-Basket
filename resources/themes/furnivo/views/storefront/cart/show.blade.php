@extends('storefront.layouts.app')

{{-- Bag ("Your cart") — markup and styles from the design, with the store's own
     discount codes, stock warnings and chosen options kept. --}}
@section('furnivo', true)
@section('title', __('store.cart.title'))
@section('noindex', true)

@php
    $taxLabel = settings()->bool('tax.enabled') && settings()->bool('tax.prices_include_tax') ? settings()->trans('tax.label') : null;
    $currency = app(\App\Services\Currency\CurrencyService::class)->current();
@endphp

@section('content')
    <section style="width: 100%; padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp; <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.theme.cart') }}</span>
        </nav>
        <h1 style="margin: 0px 0px 36px; font-size: clamp(32px, 4vw, 48px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.theme.your_cart') }}</h1>

        @foreach ($summary->notices as $notice)
            <p role="status" style="margin: 0px 0px 20px; padding: 14px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 14px; color: rgb(32, 32, 32);">{{ $notice }}</p>
        @endforeach

        @if (empty($summary->lines))
            <p style="margin: 0px 0px 24px; font-size: 16px; color: rgb(139, 135, 127);">Your cart is empty.</p>
            <a href="{{ store_route('shop') }}" class="scpb" style="display: inline-flex; align-items: center; justify-content: center; height: 54px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; transition: opacity 250ms;">{{ __('store.theme.continue_browsing') }}</a>
        @else
            <div class="cart-cols fx-cart-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: clamp(30px, 5vw, 64px); align-items: start;">
                <div style="min-width: 0px;">
                    @foreach ($summary->lines as $line)
                        @php
                            $product = $line->product;
                            $url = store_route('products.show', ['slug' => $product->slug]);
                            $media = $line->variant->image ?? $product->media->first();
                            $max = min($line->available ?? \App\Services\Cart\CartService::MAX_LINE_QUANTITY, \App\Services\Cart\CartService::MAX_LINE_QUANTITY);
                        @endphp
                        <div style="display: grid; grid-template-columns: 96px minmax(0, 1fr) auto; gap: 20px; padding: 24px 0px; border-bottom: 1px solid rgb(236, 236, 236); align-items: center;">
                            <a href="{{ $url }}" style="width: 96px; height: 96px; overflow: hidden; background: rgb(239, 236, 231); position: relative; display: block;">
                                @if ($media)<img src="{{ $media->url('thumb') }}" alt="{{ $product->name }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                            </a>
                            <div style="min-width: 0px;">
                                @if ($product->brandRecord)
                                    <div style="font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 4px;"><span>{{ $product->brandRecord->name }}</span></div>
                                @endif
                                <div style="font-size: 18px; font-weight: 600; color: rgb(32, 32, 32); margin-bottom: 3px;"><a href="{{ $url }}" style="color: inherit;"><span>{{ $product->name }}</span></a></div>
                                @if ($spec = \App\Support\RichText::text($product->short_description))
                                    <div style="font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 12px;"><span>{{ $spec }}</span></div>
                                @endif
                                @if ($title = $line->variant->title())
                                    <div style="font-size: 13px; color: rgb(139, 135, 127); margin-bottom: 8px;">{{ $title }}</div>
                                @endif
                                @if ($options = $line->item->optionsLabel())
                                    <div style="font-size: 13px; line-height: 1.5; color: rgb(139, 135, 127); margin-bottom: 8px;">{{ $options }}</div>
                                @endif
                                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; border: 1px solid rgb(217, 213, 207);">
                                        <form method="POST" action="{{ store_route('cart.update', ['item' => $line->item->id]) }}" style="margin: 0px; display: flex;">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $line->item->quantity - 1 }}">
                                            <button type="submit" aria-label="{{ __('store.product.decrease') }}" style="width: 34px; height: 38px; border: none; background: transparent; cursor: pointer; font-size: 16px;">–</button>
                                        </form>
                                        <span style="width: 32px; text-align: center; font-size: 15px;"><span>{{ $line->item->quantity }}</span></span>
                                        <form method="POST" action="{{ store_route('cart.update', ['item' => $line->item->id]) }}" style="margin: 0px; display: flex;">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $line->item->quantity + 1 }}">
                                            <button type="submit" aria-label="{{ __('store.product.increase') }}" @disabled($line->item->quantity >= $max) style="width: 34px; height: 38px; border: none; background: transparent; cursor: pointer; font-size: 16px;{{ $line->item->quantity >= $max ? ' opacity: 0.35; cursor: default;' : '' }}">+</button>
                                        </form>
                                    </div>
                                    <form method="POST" action="{{ store_route('cart.remove', ['item' => $line->item->id]) }}" style="margin: 0px;">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="border: none; background: transparent; color: rgb(102, 102, 102); font-size: 13px; text-decoration: underline; cursor: pointer;">{{ __('store.theme.remove') }}</button>
                                    </form>
                                </div>
                                @if ($line->available !== null && $line->available <= 5)
                                    <p style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ trans_choice('store.product.only_left_choice', $line->available, ['count' => $line->available]) }}</p>
                                @endif
                            </div>
                            <div style="font-size: 17px; color: rgb(32, 32, 32); white-space: nowrap;"><x-store.fv-price :money="$line->lineTotal" style="display: inline-flex; align-items: center;" /></div>
                        </div>
                    @endforeach
                </div>

                <aside style="min-width: 0px; border: 1px solid rgb(236, 236, 236); padding: 28px;" aria-label="{{ __('store.cart.summary') }}">
                    <h2 style="margin: 0px 0px 22px; font-size: 18px; letter-spacing: 0.02em;">{{ __('store.theme.order_summary') }}</h2>
                    <div style="display: flex; justify-content: space-between; font-size: 15px; color: rgb(102, 102, 102); margin-bottom: 12px;">
                        <span>{{ __('store.theme.subtotal') }}</span><span style="color: rgb(32, 32, 32);"><x-store.fv-price :money="$summary->subtotal" style="display: inline-flex; align-items: center;" /></span>
                    </div>
                    @foreach ($summary->promotions as $deal)
                        <div style="display: flex; justify-content: space-between; font-size: 15px; color: rgb(102, 102, 102); margin-bottom: 12px;">
                            <span>{{ __('store.deals.line', ['name' => $deal['name'][app()->getLocale()] ?? $deal['name']['en']]) }}</span>
                            <span style="color: rgb(32, 32, 32);">−<x-store.fv-price :money="$deal['amount']" style="display: inline-flex; align-items: center;" /></span>
                        </div>
                    @endforeach
                    @if ($summary->discount)
                        <div style="display: flex; justify-content: space-between; font-size: 15px; color: rgb(102, 102, 102); margin-bottom: 12px;">
                            <span>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</span>
                            <span style="color: rgb(32, 32, 32);">{{ $summary->freeShipping() ? __('store.discount.free_delivery') : '−' }}@unless ($summary->freeShipping())<x-store.fv-price :money="$summary->discountAmount" style="display: inline-flex; align-items: center;" />@endunless</span>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 15px; color: rgb(102, 102, 102); margin-bottom: 12px;">
                        <span>{{ __('store.theme.delivery') }}</span><span style="color: rgb(32, 32, 32);">{{ __('store.theme.calculated_at_checkout') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 600; color: rgb(32, 32, 32); border-top: 1px solid rgb(236, 236, 236); padding-top: 18px; margin-top: 6px;">
                        <span>Total</span><span><x-store.fv-price :money="$summary->discountedSubtotal()" style="display: inline-flex; align-items: center;" /></span>
                    </div>
                    @if ($taxLabel)
                        <p style="margin: 8px 0px 0px; font-size: 13px; color: rgb(139, 135, 127);">{{ __('store.product.tax_included', ['tax' => $taxLabel]) }}</p>
                    @endif
                    @if ($currency->code !== config('store.base_currency'))
                        <p style="margin: 8px 0px 0px; font-size: 13px; color: rgb(139, 135, 127);">{{ __('store.cart.currency_note', ['currency' => $currency->code, 'base' => config('store.base_currency')]) }}</p>
                    @endif

                    <div style="margin-top: 20px;">@include('storefront.partials.discount-form')</div>

                    <a href="{{ store_route('checkout') }}" data-tour="store-checkout" class="scpb" style="margin-top: 24px; width: 100%; height: 54px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; box-sizing: border-box; transition: opacity 250ms;">{{ __('store.theme.checkout') }}</a>
                    <div style="margin-top: 14px; text-align: center; font-size: 13px; color: rgb(102, 102, 102);">Guest checkout · no account required</div>
                </aside>
            </div>
            @include('storefront.partials.discount-forms-outer')
        @endif
    </section>
@endsection
