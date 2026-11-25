@extends('admin.layouts.app')

@section('title', __('admin.online.preferences'))

@section('content')
    <x-admin.page-header :title="__('admin.online.preferences')" :back="route('admin.online-store.themes')" />

    <form method="POST" action="{{ route('admin.online-store.preferences.save') }}" class="space-y-6" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
        @csrf @method('PUT')
        <x-admin.form-section :title="__('admin.online.password_title')" :description="__('admin.online.password_text')">
            <x-admin.toggle name="password_enabled" :label="__('admin.online.password_enable')" :checked="$values['password_enabled'] ?? false" />
            <x-admin.input name="password" type="password" :label="__('admin.online.password')" autocomplete="new-password" maxlength="100"
                :help="filled($values['password_hash'] ?? null) ? __('admin.online.password_keep') : __('admin.online.password_help')" />
            <x-admin.translatable-input name="password_message" :label="__('admin.online.password_message')" :values="(array) ($values['password_message'] ?? [])" multiline :rows="2" maxlength="300" />
            <p class="text-xs text-muted">{{ __('admin.online.password_note') }}</p>
        </x-admin.form-section>
        <x-admin.form-section :title="__('admin.online.tour_title')" :description="__('admin.online.tour_text')">
            <x-admin.toggle name="storefront_tour" :label="__('admin.online.tour_enable')" :checked="$values['storefront_tour'] ?? true" />
        </x-admin.form-section>
        <x-admin.form-section :title="__('admin.online.product_defaults_title')" :description="__('admin.online.product_defaults_text')">
            <x-admin.translatable-input name="product_short_description" :label="__('admin.products.fields.short_description')" :values="(array) ($productPage['short_description'] ?? [])" rich :rows="3" />
            <x-admin.translatable-input name="product_dimensions" :label="__('admin.products.fields.dimensions')" :values="(array) ($productPage['dimensions'] ?? [])" rich :rows="4" />
            <x-admin.translatable-input name="product_delivery_returns" :label="__('admin.products.fields.delivery_returns')" :values="(array) ($productPage['delivery_returns'] ?? [])" rich :rows="4" />
            <p class="text-xs text-muted">{{ __('admin.online.product_defaults_note') }}</p>
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>
@endsection
