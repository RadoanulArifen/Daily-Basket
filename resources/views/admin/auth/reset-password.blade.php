@extends('admin.layouts.auth')

@section('title', __('admin.auth.reset_title'))
@section('heading', __('admin.auth.reset_title'))

@section('content')
    <form method="POST" action="{{ route('admin.password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-admin.input name="email" type="email" :label="__('admin.auth.email')" :value="$email" required autocomplete="username" />
        <x-admin.input name="password" type="password" :label="__('admin.auth.new_password')" required autofocus autocomplete="new-password" :help="__('admin.auth.password_rules')" />
        <x-admin.input name="password_confirmation" type="password" :label="__('admin.auth.confirm_password')" required autocomplete="new-password" />
        <x-admin.button class="w-full">{{ __('admin.auth.save_password') }}</x-admin.button>
    </form>
@endsection
