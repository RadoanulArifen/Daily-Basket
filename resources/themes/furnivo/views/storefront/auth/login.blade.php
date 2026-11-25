@extends('storefront.layouts.app')

@section('furnivo', true)
@section('title', __('store.auth.login'))
@section('noindex', true)

@section('content')
    <x-store.auth-card :title="__('store.auth.login')" :intro="__('store.auth.login_intro')">
        <form method="POST" action="{{ store_route('login.store') }}">
            @csrf
            <x-store.auth-field name="email" :label="__('store.auth.email')" type="email" :value="old('email')" autocomplete="email" required />
            <x-store.auth-field name="password" :label="__('store.auth.password')" type="password" autocomplete="current-password" required />

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 4px 0px 22px;">
                <label style="display: flex; align-items: center; gap: 9px; font-size: 14px; color: rgb(102, 102, 102); cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                    {{ __('store.auth.remember') }}
                </label>
                <a href="{{ store_route('password.request') }}" style="font-size: 14px; color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.auth.forgot') }}</a>
            </div>

            <button style="width: 100%; height: 54px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.auth.login') }}</button>
        </form>

        <p style="margin: 22px 0px 0px; font-size: 15px; color: rgb(102, 102, 102);">
            {{ __('store.auth.no_account') }} <a href="{{ store_route('register') }}" style="color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.auth.create_account') }}</a>
        </p>
    </x-store.auth-card>
@endsection
