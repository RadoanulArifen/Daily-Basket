@extends('admin.layouts.auth')

@section('title', __('admin.auth.login_title'))
@section('heading', __('admin.auth.login_title'))

@section('content')
    @if ($demoRoles !== [])
        <section class="mb-5 rounded-xl border border-brand-100 bg-brand-50 p-4">
            <h2 class="font-semibold text-brand-700">{{ __('admin.auth.demo_title') }}</h2>
            <p class="mt-1 text-sm text-ink-soft">{{ __('admin.auth.demo_roles_note') }}</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($demoRoles as $role)
                    <form method="POST" action="{{ route('admin.login.demo', $role->value) }}">
                        @csrf
                        <x-admin.button class="w-full" variant="secondary">{{ $role->label() }}</x-admin.button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    @if (config('store.demo'))
        {{-- A public demo: hand the visitor the sign-in rather than making them ask. --}}
        @php
            $demoEmail = config('store.demo_email');
            $demoPassword = config('store.demo_password');
        @endphp
        <div x-data class="mb-5 rounded-xl border border-line bg-canvas p-4 text-[13px]">
            <p class="font-medium text-ink-strong">{{ __('admin.auth.demo_title') }}</p>
            <dl class="mt-2 space-y-1 text-ink-soft">
                <div class="flex items-center gap-2">
                    <dt class="w-20 shrink-0 text-muted">{{ __('admin.auth.email') }}</dt>
                    <dd class="font-mono" dir="ltr">{{ $demoEmail }}</dd>
                </div>
                <div class="flex items-center gap-2">
                    <dt class="w-20 shrink-0 text-muted">{{ __('admin.auth.password') }}</dt>
                    <dd class="font-mono" dir="ltr">{{ $demoPassword }}</dd>
                </div>
            </dl>
            <button type="button"
                    @click="document.querySelector('[data-login] [name=email]').value = @js($demoEmail);
                            document.querySelector('[data-login] [name=password]').value = @js($demoPassword);"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-line-strong bg-surface px-3 py-1.5 text-[13px] font-medium shadow-xs hover:bg-canvas">
                {{ __('admin.auth.demo_fill') }}
            </button>
            <p class="mt-3 text-[12px] text-muted">{{ __('admin.auth.demo_note') }}</p>
        </div>
    @endif

    <div data-login>
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf
        <x-admin.input name="email" type="email" :label="__('admin.auth.email')" required autofocus autocomplete="username" />
        <x-admin.input name="password" type="password" :label="__('admin.auth.password')" required autocomplete="current-password" />

        <div class="flex items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-ink-soft">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-line-strong text-brand-600 accent-brand-600">
                {{ __('admin.auth.remember') }}
            </label>
            <a href="{{ route('admin.password.request') }}" class="text-sm font-medium text-brand-600 hover:underline">{{ __('admin.auth.forgot') }}</a>
        </div>

        <x-admin.button class="w-full">{{ __('admin.auth.login') }}</x-admin.button>
    </form>
    </div>
@endsection
