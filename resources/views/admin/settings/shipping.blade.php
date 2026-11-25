@extends('admin.settings.layout')

@section('title', __('admin.settings.shipping.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.settings.shipping.title')" :back="route('admin.settings.index')" />

    <form method="POST" action="{{ route('admin.settings.shipping.update') }}" x-data="{ dirty: false, free: @js((bool) old('free_enabled', $values['free_enabled'] ?? true)) }" @change="dirty = true" class="space-y-8">
        @csrf
        @method('PUT')

        <x-admin.form-section data-tour="shipping-countries" :title="__('admin.settings.shipping.countries_title')" :description="__('admin.settings.shipping.countries_text')">
            @php
                $countryNames = \App\Support\Countries::all();
                $chosen = array_values((array) old('countries', $values['countries'] ?? []));
            @endphp
            <div x-data="{ search: '', chosen: @js($chosen) }" class="space-y-3">
                <div class="flex flex-wrap items-center gap-3">
                    <input type="search" x-model="search" placeholder="{{ __('admin.settings.shipping.countries_search') }}" aria-label="{{ __('admin.settings.shipping.countries_search') }}" class="admin-field max-w-xs">
                    <span class="text-[13px] text-muted"><span x-text="chosen.length">{{ count($chosen) }}</span> {{ __('admin.settings.shipping.countries_chosen') }}</span>
                    <button type="button" class="text-[13px] font-medium underline" x-show="chosen.length" @click="chosen = []; $dispatch('change')">{{ __('admin.settings.shipping.countries_clear') }}</button>
                </div>
                <div class="max-h-72 overflow-y-auto rounded-lg border border-line p-3">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($countryNames as $code => $countryName)
                            <label class="flex items-center gap-2 text-[13px]" x-show="! search || @js(mb_strtolower($countryName)).includes(search.toLowerCase())">
                                <input type="checkbox" name="countries[]" value="{{ $code }}" x-model="chosen" @checked(in_array($code, $chosen, true)) class="size-4 rounded">
                                <span>{{ $countryName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @error('countries')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div class="max-w-xs">
                <label for="default_country" class="mb-1 block text-[13px] font-medium">{{ __('admin.settings.shipping.default_country') }}</label>
                <select id="default_country" name="default_country" class="admin-field">
                    <option value="">{{ __('admin.settings.shipping.default_country_auto', ['country' => \App\Support\Countries::name(config('store.default_country', 'AE'))]) }}</option>
                    @foreach ($countryNames as $code => $countryName)
                        <option value="{{ $code }}" @selected(old('default_country', $values['default_country'] ?? '') === $code)>{{ $countryName }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.form-section>

        <x-admin.form-section data-tour="shipping-rates" :title="__('admin.settings.shipping.flat_title')" :description="__('admin.settings.shipping.flat_text')">
            <x-admin.toggle name="flat_enabled" :label="__('admin.settings.shipping.flat_enabled')" :checked="$values['flat_enabled'] ?? true" />
            <x-admin.translatable-input name="flat_name" :label="__('admin.settings.shipping.flat_name')" :values="(array) ($values['flat_name'] ?? [])" required maxlength="60" />
            <x-admin.translatable-input name="flat_description" :label="__('admin.settings.shipping.flat_description')" :values="(array) ($values['flat_description'] ?? [])" maxlength="120" :help="__('admin.settings.shipping.flat_description_help')" />
            <div class="max-w-xs">
                <x-admin.input name="flat_rate" type="number" step="0.01" min="0" :label="__('admin.settings.shipping.flat_rate')" :value="$values['flat_rate'] ?? '0.00'" :prefix="config('store.base_currency')" dir="ltr" required />
            </div>
        </x-admin.form-section>

        <x-admin.form-section data-tour="shipping-free" :title="__('admin.settings.shipping.free_title')" :description="__('admin.settings.shipping.free_text')">
            <x-admin.toggle name="free_enabled" :label="__('admin.settings.shipping.free_enabled')" :checked="$values['free_enabled'] ?? true" x-on:change="free = $event.target.checked" />
            <div class="max-w-xs" x-show="free">
                <x-admin.input name="free_threshold" type="number" step="0.01" min="0" :label="__('admin.settings.shipping.free_threshold')" :value="$values['free_threshold'] ?? ''" :prefix="config('store.base_currency')" dir="ltr" />
            </div>
        </x-admin.form-section>

        @include('admin.settings._save-bar')
    </form>

    {{-- Aramex (separate form: account details are saved on their own, encrypted) --}}
    @php
        $shipping = settings()->group('shipping');
        $tested = filled($shipping['aramex_tested_at'] ?? null);
        $aramexOn = $aramexConfigured && $tested && ($shipping['aramex_enabled'] ?? false);
        $mode = $shipping['aramex_mode'] ?? 'test';
    @endphp
    <x-admin.form-section :title="__('admin.settings.shipping.aramex_title')" :description="__('admin.credentials.aramex_text')" class="mt-8" id="aramex">
        <div class="flex flex-wrap items-center gap-2 text-[13px]">
            @if (! $aramexConfigured)
                <x-admin.badge dot="empty">{{ __('admin.credentials.status.not_connected') }}</x-admin.badge>
            @elseif (! $tested)
                <x-admin.badge tone="warning" dot="half">{{ __('admin.credentials.status.not_tested') }}</x-admin.badge>
            @else
                <x-admin.badge tone="success" dot="full">{{ __('admin.credentials.status.tested', ['date' => \Illuminate\Support\Carbon::parse($shipping['aramex_tested_at'])->timezone(config('store.display_timezone'))->translatedFormat('j M, H:i')]) }}</x-admin.badge>
            @endif
            <x-admin.badge :tone="$mode === 'live' ? 'info' : 'neutral'">{{ __('admin.credentials.modes.'.$mode) }}</x-admin.badge>
        </div>

        <form method="POST" action="{{ route('admin.settings.credentials.update', 'aramex') }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'username', 'label' => __('admin.credentials.aramex.username'), 'placeholder' => 'you@company.ae'])
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'password', 'label' => __('admin.credentials.aramex.password')])
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'account_number', 'label' => __('admin.credentials.aramex.account_number')])
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'account_pin', 'label' => __('admin.credentials.aramex.account_pin')])
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'account_entity', 'label' => __('admin.credentials.aramex.account_entity'), 'placeholder' => 'DXB'])
                @include('admin.settings.partials.credential-field', ['group' => 'aramex', 'field' => 'account_country', 'label' => __('admin.credentials.aramex.account_country'), 'placeholder' => 'AE'])
            </div>
            <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-3">
                <div>
                    <label for="aramex-mode" class="mb-1 block text-[13px] font-medium">{{ __('admin.credentials.mode') }}</label>
                    <select id="aramex-mode" name="mode" class="admin-field">
                        @foreach (['test', 'live'] as $m)<option value="{{ $m }}" @selected($mode === $m)>{{ __('admin.credentials.modes.'.$m) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="aramex-origin" class="mb-1 block text-[13px] font-medium">{{ __('admin.credentials.origin') }}</label>
                    <select id="aramex-origin" name="origin_city" class="admin-field">
                        @foreach (\App\Services\Shipping\AramexCarrier::CITIES as $code => $city)<option value="{{ $city }}" @selected(($shipping['aramex_origin_city'] ?? 'Dubai') === $city)>{{ __('store.emirates.'.$code) }}</option>@endforeach
                    </select>
                </div>
                <x-admin.input name="product_type" :label="__('admin.credentials.product_type')" :value="$shipping['aramex_product_type'] ?? 'ONP'" maxlength="10" dir="ltr" :help="__('admin.credentials.product_type_help')" />
            </div>
            <x-admin.translatable-input name="eta" :label="__('admin.credentials.eta')" :values="(array) ($shipping['aramex_eta'] ?? [])" maxlength="80" :help="__('admin.credentials.eta_help')" />
            <div class="flex flex-wrap items-center gap-2 border-t border-line pt-4">
                <x-admin.button>{{ __('admin.credentials.save') }}</x-admin.button>
                <p class="text-xs text-muted">{{ __('admin.credentials.aramex_retest') }}</p>
            </div>
        </form>

        @if ($aramexConfigured)
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.settings.credentials.test', 'aramex') }}">@csrf<x-admin.button variant="secondary" icon="circle-check">{{ __('admin.credentials.test') }}</x-admin.button></form>
                <form method="POST" action="{{ route('admin.settings.credentials.forget', 'aramex') }}" x-data @submit="if (! confirm(@js(__('admin.credentials.remove_confirm')))) $event.preventDefault()">
                    @csrf @method('DELETE')<x-admin.button variant="ghost" class="text-danger-600">{{ __('admin.credentials.remove') }}</x-admin.button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.settings.shipping.aramex') }}" class="flex items-center justify-between gap-4 rounded-lg bg-subdued p-3">
                @csrf @method('PUT')
                <span>
                    <span class="block text-[13px] font-semibold">{{ __('admin.credentials.aramex_checkout') }}</span>
                    <span class="block text-xs text-muted">{{ $tested ? __('admin.credentials.aramex_checkout_help') : __('admin.credentials.test_first') }}</span>
                </span>
                <input type="hidden" name="enabled" value="{{ $aramexOn ? '0' : '1' }}">
                <x-admin.button :variant="$aramexOn ? 'secondary' : 'primary'" :disabled="! $tested && ! $aramexOn">{{ $aramexOn ? __('admin.credentials.turn_off') : __('admin.credentials.turn_on') }}</x-admin.button>
            </form>
        @endif
    </x-admin.form-section>
@endsection
