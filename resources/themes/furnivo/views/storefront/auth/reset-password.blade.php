@extends('storefront.layouts.app')

@section('furnivo', true)
@section('title', __('store.auth.reset_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-card :title="__('store.auth.reset_title')" :intro="__('store.auth.password_rules')">
        <form method="POST" action="{{ store_route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">
            <x-store.auth-field name="email" :label="__('store.auth.email')" type="email" :value="old('email', request()->query('email'))" autocomplete="email" required />
            <x-store.auth-field name="password" :label="__('store.auth.new_password')" type="password" autocomplete="new-password" required />
            <x-store.auth-field name="password_confirmation" :label="__('store.auth.confirm_password')" type="password" autocomplete="new-password" required />

            <button style="width: 100%; height: 54px; margin-top: 6px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.auth.save_password') }}</button>
        </form>
    </x-store.auth-card>
@endsection
