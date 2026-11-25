@extends('storefront.layouts.app')

@section('title', __('store.auth.forgot_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-shell :heading="__('store.auth.forgot_title')" :intro="__('store.auth.forgot_intro')">
        <form method="POST" action="{{ store_route('password.email') }}" class="space-y-5">
            @csrf
            <x-store.input name="email" type="email" :label="__('store.auth.email')" required autofocus autocomplete="email" />
            <x-store.button class="w-full">{{ __('store.auth.send_link') }}</x-store.button>
        </form>
        <p class="mt-8 text-[15px]">
            <a href="{{ store_route('login') }}" class="underline underline-offset-4">{{ __('store.auth.back_to_login') }}</a>
        </p>
    </x-store.auth-shell>
@endsection
