@extends('storefront.layouts.app')

@section('title', __('store.auth.login_title'))
@section('noindex', true)

@section('content')
    <x-store.auth-shell :heading="__('store.auth.login_title')" :intro="__('store.auth.login_intro')">
        <form method="POST" action="{{ store_route('login.store') }}" class="space-y-5">
            @csrf
            <x-store.input name="email" type="email" :label="__('store.auth.email')" required autofocus autocomplete="email" />
            <x-store.input name="password" type="password" :label="__('store.auth.password')" required autocomplete="current-password" />

            <div class="flex items-center justify-between gap-4 text-sm">
                <label class="inline-flex items-center gap-2 text-ink-soft">
                    <input type="checkbox" name="remember" value="1" class="size-4 accent-ink">
                    {{ __('store.auth.remember') }}
                </label>
                <a href="{{ store_route('password.request') }}" class="underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.auth.forgot') }}</a>
            </div>

            <x-store.button class="w-full">{{ __('store.auth.login') }}</x-store.button>
        </form>

        @if ($demoAvailable)
            <form method="POST" action="{{ store_route('login.demo') }}" class="mt-4">
                @csrf
                <x-store.button class="w-full">{{ __('store.auth.demo_login') }}</x-store.button>
                <p class="mt-2 text-center text-sm text-ink-soft">{{ __('store.auth.demo_read_only') }}</p>
            </form>
        @endif

        <p class="mt-8 border-t border-line pt-6 text-[15px] text-ink-soft">
            {{ __('store.auth.no_account') }}
            <a href="{{ store_route('register') }}" class="font-medium text-ink underline underline-offset-4">{{ __('store.auth.create_account') }}</a>
        </p>
    </x-store.auth-shell>
@endsection
