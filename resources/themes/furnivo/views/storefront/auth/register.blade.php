@extends('storefront.layouts.app')

@section('furnivo', true)
@section('title', __('store.auth.register_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-card :title="__('store.auth.register_title')" :intro="__('store.auth.register_intro')">
        <form method="POST" action="{{ store_route('register.store') }}">
            @csrf
            <x-store.auth-field name="name" :label="__('store.auth.full_name')" :value="old('name')" autocomplete="name" required />
            <x-store.auth-field name="email" :label="__('store.auth.email')" type="email" :value="old('email')" autocomplete="email" required />
            <x-store.auth-field name="phone" :label="__('store.auth.phone')" :value="old('phone')" autocomplete="tel" dir="ltr" />
            <x-store.auth-field name="password" :label="__('store.auth.password')" type="password" autocomplete="new-password" required />
            <x-store.auth-field name="password_confirmation" :label="__('store.auth.confirm_password')" type="password" autocomplete="new-password" required />

            <button style="width: 100%; height: 54px; margin-top: 6px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.auth.create_account') }}</button>
        </form>

        <p style="margin: 22px 0px 0px; font-size: 15px; color: rgb(102, 102, 102);">
            {{ __('store.auth.have_account') }} <a href="{{ store_route('login') }}" style="color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.auth.login') }}</a>
        </p>
    </x-store.auth-card>
@endsection
