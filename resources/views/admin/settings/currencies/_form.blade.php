@php
    $isBase = (bool) $currency->is_base;
    $rate = old('exchange_rate', $currency->exchange_rate ? rtrim(rtrim((string) $currency->exchange_rate, '0'), '.') : null);
@endphp

<div x-data="{
        dirty: false,
        code: @js(old('code', $currency->code ?? '')),
        rate: @js((string) $rate),
        decimals: @js((string) old('decimal_places', $currency->decimal_places ?? 2)),
        get preview() {
            const r = parseFloat(this.rate), d = parseInt(this.decimals) || 0;
            if (! r || ! this.code) return null;
            return (100 * r).toFixed(d) + ' ' + this.code.toUpperCase();
        },
    }" @input="dirty = true" @change="dirty = true" class="space-y-8">

    <x-admin.form-section :title="__('admin.currencies.details_title')" :description="__('admin.currencies.details_text')">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="code" :label="__('admin.currencies.code')" :value="$currency->code" required maxlength="3"
                placeholder="USD" x-model="code" class="uppercase" dir="ltr" :readonly="$isBase" :help="__('admin.currencies.code_help')" />
            <x-admin.select name="decimal_places" :label="__('admin.currencies.decimals')" :value="$currency->decimal_places ?? 2"
                :options="[0 => '0 (¥1,250)', 2 => '2 (1,250.00)', 3 => '3 (1,250.000)']" x-model="decimals" />
        </div>

        <x-admin.translatable-input name="name" :label="__('admin.currencies.name')" :values="$currency->getTranslations('name')" required maxlength="60" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="symbol" :label="__('admin.currencies.symbol')" :value="$currency->symbol" required maxlength="10" placeholder="USD" :help="__('admin.currencies.symbol_help')" />
            <x-admin.input name="symbol_ar" :label="__('admin.currencies.symbol_ar')" :value="$currency->symbol_ar" maxlength="10" placeholder="دولار" dir="rtl" lang="ar" />
        </div>
    </x-admin.form-section>

    <x-admin.form-section :title="__('admin.currencies.rate_title')" :description="__('admin.currencies.rate_text', ['base' => $base->code])">
        @if ($isBase)
            <x-admin.alert>{{ __('admin.currencies.base_rate_fixed') }}</x-admin.alert>
        @else
            <x-admin.input name="exchange_rate" type="number" step="0.00000001" min="0" :label="__('admin.currencies.rate')"
                :value="$rate" required :prefix="'1 '.$base->code.' ='" x-model="rate" dir="ltr" :help="__('admin.currencies.rate_help')" />

            <p x-show="preview" x-cloak class="rounded-lg bg-canvas px-4 py-3 text-sm text-ink-soft" dir="ltr">
                100.00 {{ $base->code }} = <span class="font-medium text-ink" x-text="preview"></span>
            </p>
        @endif
    </x-admin.form-section>

    <x-admin.form-section :title="__('admin.currencies.visibility_title')" :description="__('admin.currencies.visibility_text')">
        @if ($isBase)
            <input type="hidden" name="is_active" value="1">
            <x-admin.toggle name="is_active_display" :label="__('admin.currencies.active')" :description="__('admin.currencies.base_always_active')" checked disabled />
        @else
            <x-admin.toggle name="is_active" :label="__('admin.currencies.active')" :description="__('admin.currencies.active_help')" :checked="$currency->is_active ?? true" />
        @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.input name="sort_order" type="number" min="0" max="999" :label="__('admin.common.sort_order')" :value="$currency->sort_order ?? 0" :help="__('admin.common.sort_order_help')" />
        </div>
    </x-admin.form-section>

    @include('admin.settings._save-bar', ['always' => ! $currency->exists])
</div>
