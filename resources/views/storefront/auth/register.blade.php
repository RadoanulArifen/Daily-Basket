@extends('storefront.layouts.app')

@section('title', __('store.auth.register_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-shell :heading="__('store.auth.register_title')" :intro="__('store.auth.register_intro')">
        <form method="POST" action="{{ store_route('register.store') }}" class="space-y-5">
            @csrf
            <x-store.input name="name" :label="__('store.auth.full_name')" required autofocus autocomplete="name" />
            <x-store.input name="email" type="email" :label="__('store.auth.email')" required autocomplete="email" />
            <x-store.input name="phone" type="tel" :label="__('store.auth.phone')" autocomplete="tel" placeholder="+971 50 123 4567" dir="ltr" :help="__('store.auth.phone_help')" />
            <x-store.input name="password" type="password" :label="__('store.auth.password')" required autocomplete="new-password" :help="__('store.auth.password_rules')" />
            <x-store.input name="password_confirmation" type="password" :label="__('store.auth.confirm_password')" required autocomplete="new-password" />

            <x-store.button class="w-full">{{ __('store.auth.create_account') }}</x-store.button>
        </form>

        <p class="mt-8 border-t border-line pt-6 text-[15px] text-ink-soft">
            {{ __('store.auth.have_account') }}
            <a href="{{ store_route('login') }}" class="font-medium text-ink underline underline-offset-4">{{ __('store.auth.login') }}</a>
        </p>
    </x-store.auth-shell>
@endsection
