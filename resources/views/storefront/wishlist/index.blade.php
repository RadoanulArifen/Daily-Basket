@extends('storefront.layouts.app')

@section('title', __('store.wishlist.title'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-10 sm:px-6 lg:px-10">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.wishlist.title') }}</h1>

        @guest('web')
            @if ($products->isNotEmpty())
                <p class="mt-3 text-[15px] text-ink-soft">
                    {{ __('store.wishlist.guest_note') }}
                    <a href="{{ store_route('login') }}" class="font-medium text-ink underline underline-offset-4">{{ __('store.auth.login') }}</a>
                </p>
            @endif
        @endguest

        @if ($products->isEmpty())
            <div class="mt-10 rounded-md border border-dashed border-line px-6 py-16 text-center">
                <x-icon name="heart" class="mx-auto size-8 text-stone" />
                <p class="mt-4 text-lg font-medium">{{ __('store.wishlist.empty_title') }}</p>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.wishlist.empty_text') }}</p>
                <x-store.button :href="store_route('shop')" class="mt-6">{{ __('store.listing.browse_all') }}</x-store.button>
            </div>
        @else
            <x-store.product-grid :products="$products" class="mt-10" />
        @endif
    </div>
@endsection
