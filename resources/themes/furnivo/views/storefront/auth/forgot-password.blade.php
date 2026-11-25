@extends('storefront.layouts.app')

@section('furnivo', true)
@section('title', __('store.auth.forgot'))
@section('noindex', true)

@section('content')
    <x-store.auth-card :title="__('store.auth.forgot')" :intro="__('store.auth.forgot_intro')">
        <form method="POST" action="{{ store_route('password.email') }}">
            @csrf
            <x-store.auth-field name="email" :label="__('store.auth.email')" type="email" :value="old('email')" autocomplete="email" required />
            <button style="width: 100%; height: 54px; margin-top: 6px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.auth.send_link') }}</button>
        </form>

        <p style="margin: 22px 0px 0px; font-size: 15px; color: rgb(102, 102, 102);">
            <a href="{{ store_route('login') }}" style="color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.auth.back_to_login') }}</a>
        </p>
    </x-store.auth-card>
@endsection
