@extends('storefront.layouts.app')

@section('title', __('store.auth.reset_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-shell :heading="__('store.auth.reset_title')">
        <form method="POST" action="{{ store_route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-store.input name="email" type="email" :label="__('store.auth.email')" :value="$email" required autocomplete="email" />
            <x-store.input name="password" type="password" :label="__('store.auth.new_password')" required autofocus autocomplete="new-password" :help="__('store.auth.password_rules')" />
            <x-store.input name="password_confirmation" type="password" :label="__('store.auth.confirm_password')" required autocomplete="new-password" />
            <x-store.button class="w-full">{{ __('store.auth.save_password') }}</x-store.button>
        </form>
    </x-store.auth-shell>
@endsection
