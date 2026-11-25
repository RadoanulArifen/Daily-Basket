@extends('storefront.layouts.app')

{{-- The store is not open to the public yet: enter the password to look around. --}}
@section('furnivo', true)
@section('title', settings()->trans('general.store_name'))
@section('noindex', true)

@section('content')
    <x-store.auth-card :title="settings()->trans('general.store_name')" :intro="$message ?: __('store.password_page.default')">
        <form method="POST" action="{{ route('store.password.unlock') }}">
            @csrf
            <x-store.auth-field name="password" :label="__('store.password_page.label')" type="password" required autocomplete="off" />
            <button style="width: 100%; height: 54px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.password_page.enter') }}</button>
        </form>

        <p style="margin: 22px 0px 0px; font-size: 14px; color: rgb(139, 135, 127);">
            {{ __('store.password_page.owner') }} <a href="{{ route('admin.login') }}" style="color: rgb(29, 29, 29); text-decoration: underline;">{{ __('store.password_page.owner_link') }}</a>
        </p>
    </x-store.auth-card>
@endsection
