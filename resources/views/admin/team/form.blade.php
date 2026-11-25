@extends('admin.settings.layout')

@section('title', $member->exists ? $member->name : __('admin.admins.add'))

@section('settings')
    <x-admin.page-header :title="$member->exists ? $member->name : __('admin.admins.add')" :back="route('admin.admins.index')" />

    <form method="POST" action="{{ $member->exists ? route('admin.admins.update', $member) : route('admin.admins.store') }}" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($member->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.admins.details_title')" :description="$member->exists ? null : __('admin.admins.invite_text')">
            <x-admin.input name="name" :label="__('admin.admins.fields.name')" :value="$member->name" required maxlength="120" />
            <x-admin.input name="email" type="email" :label="__('admin.admins.fields.email')" :value="config('store.demo') && $member->id !== auth('admin')->id() ? \Illuminate\Support\Str::mask($member->email, '•', 1, max(1, strpos($member->email, '@') - 1)) : $member->email" required dir="ltr" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.admins.fields.role')" :description="__('admin.admins.role_text')">
            <fieldset class="space-y-2">
                <legend class="sr-only">{{ __('admin.admins.fields.role') }}</legend>
                @foreach (\App\Enums\AdminRole::cases() as $role)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line-strong p-3 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                        <input type="radio" name="role" value="{{ $role->value }}" @checked(old('role', $member->role?->value) === $role->value) class="mt-0.5 accent-brand-600">
                        <span><span class="block text-sm font-medium">{{ $role->label() }}</span><span class="block text-[13px] text-muted">{{ $role->description() }}</span></span>
                    </label>
                @endforeach
            </fieldset>
            <x-admin.field-error name="role" />
            @if ($member->exists)
                <x-admin.toggle name="is_active" :label="__('admin.admins.fields.active')" :description="__('admin.admins.active_help')" :checked="$member->is_active" />
            @endif
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $member->exists, 'label' => $member->exists ? null : __('admin.admins.send_invite')])
    </form>

    @if ($member->exists && ! $member->is(auth('admin')->user()))
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('admin.admins.resend', $member) }}">@csrf<x-admin.button variant="secondary" icon="mail">{{ __('admin.admins.resend') }}</x-admin.button></form>
            <x-admin.confirm-delete :action="route('admin.admins.destroy', $member)" :label="__('admin.admins.delete')" :title="__('admin.admins.delete_title', ['name' => $member->name])" :message="__('admin.admins.delete_text')" />
        </div>
    @endif
@endsection
