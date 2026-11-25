@extends('storefront.layouts.app')

@section('title', $address->exists ? __('store.addresses.edit_title') : __('store.addresses.add'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-2xl px-4 pb-20 pt-10 sm:px-6">
        <x-store.breadcrumbs :items="[['label' => __('store.account.title'), 'url' => store_route('account.dashboard')], ['label' => __('store.addresses.title'), 'url' => store_route('account.addresses')], ['label' => $address->exists ? __('store.addresses.edit_title') : __('store.addresses.add')]]" />
        <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $address->exists ? __('store.addresses.edit_title') : __('store.addresses.add') }}</h1>

        <form method="POST" action="{{ $address->exists ? store_route('account.addresses.update', ['address' => $address->id]) : store_route('account.addresses.store') }}" class="mt-8 grid gap-4 sm:grid-cols-2">
            @csrf
            @if ($address->exists) @method('PUT') @endif
            <x-store.input name="label" :label="__('store.addresses.label')" :value="old('label', $address->label)" maxlength="40" :placeholder="__('store.addresses.label_placeholder')" class="sm:col-span-2" />
            <x-store.input name="name" :label="__('store.checkout.full_name')" :value="old('name', $address->name ?? auth('web')->user()->name)" required autocomplete="name" />
            <x-store.input name="phone" type="tel" :label="__('store.auth.phone')" :value="old('phone', $address->phone ?? auth('web')->user()->phone)" required dir="ltr" autocomplete="tel" />
            <div class="space-y-2">
                <label for="country" class="block text-sm font-medium">{{ __('store.checkout.country') }}</label>
                <select id="country" name="country" required autocomplete="country" class="store-field">
                    @foreach (\App\Support\Countries::allowed() as $code => $countryName)
                        <option value="{{ $code }}" @selected(old('country', $address->country ?: \App\Support\Countries::default()) === $code)>{{ $countryName }}</option>
                    @endforeach
                </select>
                @error('country')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            </div>
            <x-store.input name="state" :label="__('store.checkout.state')" :value="old('state', $address->state)" autocomplete="address-level1" :placeholder="__('store.checkout.state_hint')" />
            <x-store.input name="city" :label="__('store.checkout.city')" :value="old('city', $address->city)" required />
            <x-store.input name="address_line" :label="__('store.checkout.address_line')" :value="old('address_line', $address->address_line)" required class="sm:col-span-2" />
            <x-store.input name="building" :label="__('store.checkout.building')" :value="old('building', $address->building)" class="sm:col-span-2" />
            @unless ($address->is_default && $address->exists)
                <label class="flex items-center gap-3 text-[15px] sm:col-span-2"><input type="checkbox" name="is_default" value="1" class="size-4 accent-ink"> {{ __('store.addresses.use_as_default') }}</label>
            @endunless
            <div class="flex gap-3 sm:col-span-2">
                <x-store.button>{{ __('store.addresses.save') }}</x-store.button>
                <x-store.button :href="store_route('account.addresses')" variant="secondary">{{ __('store.addresses.cancel') }}</x-store.button>
            </div>
        </form>
    </div>
@endsection
