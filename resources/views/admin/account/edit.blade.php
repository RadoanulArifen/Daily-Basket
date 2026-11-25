@extends('admin.settings.layout')

@section('title', __('admin.account.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.account.title')" :back="route('admin.settings.index')" />

    <div class="space-y-8">
        <form method="POST" action="{{ route('admin.account.update') }}">
            @csrf
            @method('PUT')
            <x-admin.form-section :title="__('admin.account.profile_title')" :description="__('admin.account.profile_text')">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="name" :label="__('admin.account.name')" :value="$admin->name" required autocomplete="name" />
                    <x-admin.input name="email" type="email" :label="__('admin.auth.email')" :value="$admin->email" required autocomplete="email" />
                    <x-admin.select name="locale" :label="__('admin.account.language')" :value="$admin->locale"
                        :options="collect(config('store.locales'))->map(fn ($l) => $l['native'])->all()" />
                </div>
                <div class="flex justify-end">
                    <x-admin.button>{{ __('admin.common.save') }}</x-admin.button>
                </div>
            </x-admin.form-section>
        </form>

        <form method="POST" action="{{ route('admin.account.password') }}">
            @csrf
            @method('PUT')
            <x-admin.form-section :title="__('admin.account.password_title')" :description="__('admin.account.password_text')">
                <x-admin.input name="current_password" type="password" :label="__('admin.account.current_password')" bag="password" required autocomplete="current-password" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="password" type="password" :label="__('admin.auth.new_password')" bag="password" required autocomplete="new-password" :help="__('admin.auth.password_rules')" />
                    <x-admin.input name="password_confirmation" type="password" :label="__('admin.auth.confirm_password')" bag="password" required autocomplete="new-password" />
                </div>
                <div class="flex justify-end">
                    <x-admin.button>{{ __('admin.account.update_password') }}</x-admin.button>
                </div>
            </x-admin.form-section>
        </form>

        <p class="text-[13px] text-muted">
            {{ __('admin.account.last_login') }}:
            {{ $admin->last_login_at?->timezone(config('store.display_timezone'))->translatedFormat('j M Y, H:i') ?? '—' }}
        </p>
    </div>
@endsection
