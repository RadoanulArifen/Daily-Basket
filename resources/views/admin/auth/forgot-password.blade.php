@extends('admin.layouts.auth')

@section('title', __('admin.auth.forgot_title'))
@section('heading', __('admin.auth.forgot_title'))

@section('content')
    <p class="mb-5 text-sm text-muted">{{ __('admin.auth.forgot_text') }}</p>

    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5">
        @csrf
        <x-admin.input name="email" type="email" :label="__('admin.auth.email')" required autofocus autocomplete="username" />
        <x-admin.button class="w-full">{{ __('admin.auth.send_link') }}</x-admin.button>
    </form>
@endsection

@section('footer')
    <a href="{{ route('admin.login') }}" class="font-medium text-brand-600 hover:underline">{{ __('admin.auth.back_to_login') }}</a>
@endsection
