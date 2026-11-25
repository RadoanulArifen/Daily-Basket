@extends('storefront.layouts.app')

@section('title', __('store.reminder.unsubscribed_title'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-xl px-4 py-20 text-center sm:px-6">
        <x-icon name="circle-check" class="mx-auto size-10 text-success" />
        <h1 class="mt-4 text-2xl font-semibold tracking-tight">{{ __('store.reminder.unsubscribed_title') }}</h1>
        <p class="mt-3 text-[15px] text-ink-soft">{{ __('store.reminder.unsubscribed_text', ['email' => $email]) }}</p>
        <x-store.button :href="store_route('home')" variant="secondary" class="mt-8">{{ __('store.cart.continue') }}</x-store.button>
    </div>
@endsection
