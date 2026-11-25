@extends('admin.settings.layout')

@section('title', __('admin.admins.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.admins.title')" :subtitle="__('admin.admins.subtitle')" :back="route('admin.settings.index')">
        <x-slot:actions><x-admin.button :href="route('admin.admins.create')" icon="plus">{{ __('admin.admins.add') }}</x-admin.button></x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <ul class="divide-y divide-line">
            @foreach ($admins as $member)
                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ mb_substr($member->name, 0, 1) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ $member->name }} @if ($member->is(auth('admin')->user()))<span class="text-muted">({{ __('admin.admins.you') }})</span>@endif</span>
                        <span class="block truncate text-[13px] text-muted">{{ config('store.demo') && $member->id !== auth('admin')->id() ? \Illuminate\Support\Str::mask($member->email, '•', 1, max(1, strpos($member->email, '@') - 1)) : $member->email }}</span>
                    </span>
                    <x-admin.badge :tone="$member->isOwner() ? 'brand' : 'neutral'">{{ $member->role->label() }}</x-admin.badge>
                    @unless ($member->is_active)<x-admin.badge tone="danger">{{ __('admin.admins.inactive') }}</x-admin.badge>@endunless
                    <span class="w-40 text-[13px] text-muted">{{ $member->last_login_at ? __('admin.admins.last_seen', ['date' => $member->last_login_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y')]) : __('admin.admins.never_signed_in') }}</span>
                    <x-admin.button :href="route('admin.admins.edit', $member)" variant="ghost" size="sm">{{ __('admin.common.edit') }}</x-admin.button>
                </li>
            @endforeach
        </ul>
    </x-admin.card>

    <x-admin.card :title="__('admin.admins.roles_title')" class="mt-6">
        <dl class="grid gap-4 sm:grid-cols-2">
            @foreach (\App\Enums\AdminRole::cases() as $role)
                <div><dt class="text-sm font-medium">{{ $role->label() }}</dt><dd class="mt-0.5 text-[13px] text-muted">{{ $role->description() }}</dd></div>
            @endforeach
        </dl>
    </x-admin.card>
@endsection
