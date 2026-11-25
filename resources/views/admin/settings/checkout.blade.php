@extends('admin.settings.layout')

@section('title', __('admin.settings.checkout.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.checkout.title')" :back="route('admin.settings.index')" />

    <form method="POST" action="{{ route('admin.settings.checkout.update') }}" x-data="{ dirty: false }" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section :title="__('admin.settings.checkout.accounts_title')" :description="__('admin.settings.checkout.accounts_text')">
            <x-admin.toggle name="guest_checkout" :label="__('admin.settings.checkout.guest_checkout')" :description="__('admin.settings.checkout.guest_checkout_help')" :checked="$values['guest_checkout'] ?? true" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.checkout.form_title')" :description="__('admin.settings.checkout.form_text')">
            <x-admin.toggle name="require_phone" :label="__('admin.settings.checkout.require_phone')" :description="__('admin.settings.checkout.require_phone_help')" :checked="$values['require_phone'] ?? true" />
            <x-admin.toggle name="order_notes" :label="__('admin.settings.checkout.order_notes')" :description="__('admin.settings.checkout.order_notes_help')" :checked="$values['order_notes'] ?? true" />
            <x-admin.toggle name="marketing_opt_in" :label="__('admin.settings.checkout.marketing_opt_in')" :description="__('admin.settings.checkout.marketing_opt_in_help')" :checked="$values['marketing_opt_in'] ?? true" />
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>
@endsection
