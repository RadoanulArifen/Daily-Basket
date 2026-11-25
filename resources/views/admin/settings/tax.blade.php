@extends('admin.settings.layout')

@section('title', __('admin.settings.tax.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.tax.title')" :back="route('admin.settings.index')" />

    @php
        $enabled = (bool) old('enabled', $values['enabled'] ?? true);
        $rate = old('rate', $values['rate'] ?? '5.00');
        $inclusive = (bool) old('prices_include_tax', $values['prices_include_tax'] ?? true);
    @endphp

    <form method="POST" action="{{ route('admin.settings.tax.update') }}"
          x-data="{
              dirty: false,
              enabled: @js($enabled),
              rate: @js((string) $rate),
              inclusive: @js($inclusive ? '1' : '0'),
              money(v) { return 'AED ' + (Math.round(v * 100) / 100).toFixed(2) },
              get example() {
                  const r = parseFloat(this.rate) || 0, price = 105;
                  if (this.inclusive === '1') {
                      const tax = price * r / (100 + r);
                      return { pays: this.money(price), tax: this.money(tax) };
                  }
                  return { pays: this.money(price + price * r / 100), tax: this.money(price * r / 100) };
              },
          }"
          @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section :title="__('admin.settings.tax.collect_title')" :description="__('admin.settings.tax.collect_text')">
            <x-admin.toggle name="enabled" :label="__('admin.settings.tax.enabled')" :description="__('admin.settings.tax.enabled_help')" :checked="$enabled" x-model="enabled" />

            <div x-show="enabled" x-transition class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="rate" type="number" step="0.01" min="0" max="100" :label="__('admin.settings.tax.rate')" :value="$rate" suffix="%" x-model="rate" dir="ltr" />
                </div>
                <x-admin.translatable-input name="label" :label="__('admin.settings.tax.label')" :values="$values['label'] ?? []" required maxlength="40" :help="__('admin.settings.tax.label_help')" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.settings.tax.pricing_title')" :description="__('admin.settings.tax.pricing_text')">
            <fieldset class="space-y-3">
                <legend class="sr-only">{{ __('admin.settings.tax.pricing_title') }}</legend>
                @foreach (['1' => 'included', '0' => 'excluded'] as $value => $option)
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-line p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="prices_include_tax" value="{{ $value }}" x-model="inclusive" @checked(($inclusive ? '1' : '0') === $value) class="mt-0.5 size-4 accent-brand-600">
                        <span>
                            <span class="block text-sm font-medium">{{ __("admin.settings.tax.{$option}") }}</span>
                            <span class="mt-0.5 block text-[13px] text-muted">{{ __("admin.settings.tax.{$option}_help") }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div x-show="enabled" class="rounded-lg bg-canvas p-4 text-sm">
                <p class="font-medium">{{ __('admin.settings.tax.example_title') }}</p>
                <p class="mt-1 text-ink-soft">
                    {{ __('admin.settings.tax.example_price') }}
                    <span class="font-medium text-ink" dir="ltr">AED 105.00</span>.
                    {{ __('admin.settings.tax.example_pays') }}
                    <span class="font-medium text-ink" dir="ltr" x-text="example.pays"></span>,
                    {{ __('admin.settings.tax.example_tax') }}
                    <span class="font-medium text-ink" dir="ltr" x-text="example.tax"></span>.
                </p>
            </div>

            <x-admin.toggle name="show_on_invoice" :label="__('admin.settings.tax.show_on_invoice')" :description="__('admin.settings.tax.show_on_invoice_help')" :checked="$values['show_on_invoice'] ?? true" />
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>
@endsection
