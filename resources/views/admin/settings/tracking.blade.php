@extends('admin.settings.layout')

@section('title', __('admin.settings.tracking.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.tracking.title')" :back="route('admin.settings.index')" />

    <form method="POST" action="{{ route('admin.settings.tracking.update') }}" x-data="{ dirty: false }" @input="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section :title="__('admin.settings.tracking.ga_title')" :description="__('admin.settings.tracking.ga_text')">
            <div class="max-w-sm">
                <x-admin.input name="ga_measurement_id" :label="__('admin.settings.tracking.ga_id')" :value="$values['ga_measurement_id'] ?? ''" placeholder="G-XXXXXXXXXX" dir="ltr" maxlength="24" :help="__('admin.settings.tracking.ga_help')" />
            </div>
            <x-admin.alert>{{ __('admin.settings.tracking.ga_events') }}</x-admin.alert>
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>
@endsection
