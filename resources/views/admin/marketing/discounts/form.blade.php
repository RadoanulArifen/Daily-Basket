@extends('admin.layouts.app')

@section('title', $discount->exists ? $discount->code : __('admin.discounts.add'))

@section('content')
    @php
        $tz = config('store.display_timezone');
        $local = fn ($date) => $date?->timezone($tz)->format('Y-m-d\TH:i');
    @endphp

    <x-admin.page-header :title="$discount->exists ? $discount->code : __('admin.discounts.add')" :back="route('admin.discounts.index')"
        :subtitle="$discount->exists ? trans_choice('admin.discounts.used', $discount->used_count, ['count' => $discount->used_count]) : null" />

    <form method="POST" action="{{ $discount->exists ? route('admin.discounts.update', $discount) : route('admin.discounts.store') }}"
          x-data="{ dirty: false, type: @js(old('type', $discount->type)) }" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($discount->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.discounts.code_title')" :description="__('admin.discounts.code_text')">
            <x-admin.input name="code" :label="__('admin.discounts.fields.code')" :value="$discount->code" maxlength="40" required dir="ltr" :help="__('admin.discounts.code_help')" />
            <fieldset>
                <legend class="text-sm font-medium">{{ __('admin.discounts.fields.type') }}</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Models\Discount::TYPES as $type)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm" :class="type === @js($type) ? 'border-brand-600 bg-brand-50' : 'border-line-strong'">
                            <input type="radio" name="type" value="{{ $type }}" x-model="type" class="accent-brand-600"> {{ __("admin.discounts.types.{$type}") }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div class="max-w-xs" x-show="type !== 'free_shipping'">
                <x-admin.input name="value" type="number" step="0.01" min="0" :label="__('admin.discounts.fields.value')" :value="$discount->type === 'free_shipping' ? '' : $discount->value" dir="ltr"
                    x-bind:placeholder="type === 'percentage' ? '15' : '50.00'" />
                <p class="mt-1 text-[13px] text-muted" x-text="type === 'percentage' ? @js(__('admin.discounts.value_percent_help')) : @js(__('admin.discounts.value_fixed_help', ['currency' => config('store.base_currency')]))"></p>
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.discounts.rules_title')" :description="__('admin.discounts.rules_text')">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="min_subtotal" type="number" step="0.01" min="0" :label="__('admin.discounts.fields.min_subtotal')" :value="$discount->min_subtotal?->toDecimal()" :prefix="config('store.base_currency')" dir="ltr" />
                <x-admin.input name="usage_limit" type="number" min="1" :label="__('admin.discounts.fields.usage_limit')" :value="$discount->usage_limit" dir="ltr" :help="__('admin.discounts.usage_limit_help')" />
                <x-admin.input name="usage_limit_per_customer" type="number" min="1" :label="__('admin.discounts.fields.per_customer')" :value="$discount->usage_limit_per_customer" dir="ltr" :help="__('admin.discounts.per_customer_help')" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="starts_at" type="datetime-local" :label="__('admin.discounts.fields.starts_at')" :value="$local($discount->starts_at)" />
                <x-admin.input name="ends_at" type="datetime-local" :label="__('admin.discounts.fields.ends_at')" :value="$local($discount->ends_at)" :help="__('admin.discounts.dates_help')" />
            </div>
            <x-admin.toggle name="is_active" :label="__('admin.discounts.fields.active')" :checked="$discount->is_active ?? true" />
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $discount->exists])
    </form>

    @if ($discount->exists)
        <div class="mt-6">
            <x-admin.confirm-delete :action="route('admin.discounts.destroy', $discount)" :label="__('admin.discounts.delete')" :title="__('admin.discounts.delete_title', ['code' => $discount->code])" :message="__('admin.discounts.delete_text')" />
        </div>
    @endif
@endsection
