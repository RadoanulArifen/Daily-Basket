@extends('admin.layouts.app')

@php
    // A draft fills the form back in; anything typed just now (old()) still wins.
    $draft = $draft ?? null;
    $draftValues = $draft?->payload ?? [];
    $draftLines = collect($draftValues['lines'] ?? [])->map(fn ($qty, $id) => ['id' => (int) $id, 'qty' => (int) $qty])->values()->all();
@endphp

@section('title', __('admin.draft.title'))

@section('content')
    <form method="POST" action="{{ route('admin.draft-orders.store') }}" x-data="orderBuilder({ urls: @js(['variants' => route('admin.draft-orders.variants'), 'customers' => route('admin.draft-orders.customers'), 'rates' => route('admin.draft-orders.rates')]), country: @js(old('country', $draftValues['country'] ?? \App\Support\Countries::default())), state: @js(old('state', $draftValues['state'] ?? '')), draftLines: @js($draftLines ?? []), currency: @js(app(\App\Services\Currency\CurrencyService::class)->base()->symbol.' ') })">
        @csrf
        @isset($draft)
            <input type="hidden" name="draft_id" value="{{ $draft->id }}">
        @endisset
        <input type="hidden" name="customer_id" :value="customerId">
        <x-admin.page-header :title="__('admin.draft.title')" :back="route('admin.orders.index')" />

        @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="min-w-0 space-y-4 lg:col-span-2">
                {{-- Products --}}
                <x-admin.card :title="__('admin.draft.products')">
                    <div class="relative" @click.outside="results = []">
                        <label class="relative block">
                            <span class="sr-only">{{ __('admin.draft.search_products') }}</span>
                            <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                            <input type="search" x-model="q" @input.debounce.250ms="find()" @focus="find()" placeholder="{{ __('admin.draft.search_products') }}" class="admin-field ps-8" autocomplete="off">
                        </label>
                        <ul x-show="results.length" x-cloak class="absolute inset-x-0 z-20 mt-1 max-h-80 overflow-y-auto rounded-xl bg-surface p-1.5 shadow-popover">
                            <template x-for="v in results" :key="v.id">
                                <li>
                                    <button type="button" @click="add(v)" :disabled="v.stock === 0" class="flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-start hover:bg-subdued disabled:opacity-40">
                                        <img :src="v.image" x-show="v.image" alt="" class="size-9 rounded-md border border-line object-cover">
                                        <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-medium" x-text="v.title"></span><span class="block text-xs text-muted" x-text="[v.variant, v.sku].filter(Boolean).join(' · ')"></span></span>
                                        <span class="text-xs text-muted" x-text="v.stock === null ? '' : v.stock + ' {{ __('admin.draft.available') }}'"></span>
                                        <span class="text-[13px]" dir="ltr" x-text="v.price_label"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <p x-show="! lines.length" class="py-6 text-center text-[13px] text-muted">{{ __('admin.draft.no_products') }}</p>
                    <ul x-show="lines.length" class="mt-3 divide-y divide-line border-y border-line">
                        <template x-for="l in lines" :key="l.id">
                            <li class="flex items-center gap-3 py-2.5">
                                <img :src="l.image" x-show="l.image" alt="" class="size-10 rounded-lg border border-line object-cover">
                                <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-medium" x-text="l.title"></span><span class="block text-xs text-muted" x-text="[l.variant, l.price_label].filter(Boolean).join(' · ')"></span></span>
                                <input type="number" :name="`lines[${l.id}]`" x-model.number="l.qty" @change="loadRates()" min="1" :max="l.stock ?? 999" class="admin-field w-16 text-center" dir="ltr" :aria-label="'{{ __('admin.draft.quantity') }} ' + l.title">
                                <span class="w-24 text-end text-[13px]" dir="ltr" x-text="money(l.price * (+l.qty || 0))"></span>
                                <button type="button" @click="remove(l.id)" class="rounded-lg p-1 text-muted hover:bg-subdued hover:text-danger-600" aria-label="{{ __('admin.common.remove') }}"><x-icon name="x" class="size-4" /></button>
                            </li>
                        </template>
                    </ul>
                </x-admin.card>

                {{-- Payment --}}
                <x-admin.card :title="__('admin.draft.payment')">
                    <div class="flex justify-between text-sm"><span class="text-ink-soft">{{ __('admin.draft.subtotal') }}</span><span dir="ltr" x-text="money(subtotalMinor)"></span></div>
                    <p class="mt-1 text-xs text-muted">{{ __('admin.draft.totals_note') }}</p>
                    <div class="mt-4 max-w-xs"><x-admin.input name="discount_code" :value="old('discount_code', $draftValues['discount_code'] ?? null)" :label="__('admin.draft.discount_code')" maxlength="40" class="uppercase" /></div>
                    <fieldset class="mt-4 grid gap-2 sm:grid-cols-2">
                        <legend class="mb-2 text-[13px] font-medium">{{ __('admin.draft.collect') }}</legend>
                        @foreach (['paid', 'cod'] as $option)
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg p-3 text-[13px] ring-1 ring-line has-[:checked]:bg-subdued has-[:checked]:ring-ink-strong">
                                <input type="radio" name="payment" value="{{ $option }}" @checked(old('payment', $draftValues['payment'] ?? 'cod') === $option) class="mt-0.5 accent-ink-strong">
                                <span><span class="block font-semibold">{{ __("admin.draft.payment_options.{$option}") }}</span><span class="block text-muted">{{ __("admin.draft.payment_help.{$option}") }}</span></span>
                            </label>
                        @endforeach
                    </fieldset>
                </x-admin.card>
            </div>

            <div class="min-w-0 space-y-4">
                {{-- Customer --}}
                <x-admin.card :title="__('admin.orders.customer')">
                    <div class="relative" @click.outside="customers = []">
                        <input type="search" x-model="customerQ" @input.debounce.250ms="findCustomer()" placeholder="{{ __('admin.draft.search_customers') }}" class="admin-field" autocomplete="off" aria-label="{{ __('admin.draft.search_customers') }}">
                        <ul x-show="customers.length" x-cloak class="absolute inset-x-0 z-20 mt-1 rounded-xl bg-surface p-1.5 shadow-popover">
                            <template x-for="c in customers" :key="c.id">
                                <li><button type="button" @click="pickCustomer(c)" class="w-full rounded-lg px-2 py-1.5 text-start hover:bg-subdued"><span class="block text-[13px] font-medium" x-text="c.name"></span><span class="block text-xs text-muted" x-text="c.email"></span></button></li>
                            </template>
                        </ul>
                    </div>
                    <p x-show="customerId" x-cloak class="mt-2 text-xs text-success-700">{{ __('admin.draft.existing_customer') }}</p>
                    <div class="mt-3 space-y-3">
                        <x-admin.input name="email" :value="old('email', $draftValues['email'] ?? null)" type="email" :label="__('admin.draft.email')" required dir="ltr" />
                        <x-admin.input name="phone" :value="old('phone', $draftValues['phone'] ?? null)" type="tel" :label="__('admin.draft.phone')" required dir="ltr" />
                    </div>
                </x-admin.card>

                {{-- Address + delivery --}}
                <x-admin.card :title="__('admin.orders.shipping_address')">
                    <div class="space-y-3">
                        <x-admin.input name="name" :value="old('name', $draftValues['name'] ?? null)" :label="__('admin.draft.full_name')" required maxlength="120" />
                        <div>
                            <label for="country" class="mb-1 block text-[13px] font-medium">{{ __('store.checkout.country') }}</label>
                            <select id="country" name="country" x-model="country" @change="loadRates()" class="admin-field">
                                @foreach (\App\Support\Countries::allowed() as $code => $countryName)<option value="{{ $code }}">{{ $countryName }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="state" class="mb-1 block text-[13px] font-medium">{{ __('store.checkout.state') }}</label>
                            <input id="state" name="state" x-model="state" @change="loadRates()" maxlength="100" class="admin-field" placeholder="{{ __('store.checkout.state_hint') }}">
                        </div>
                        <x-admin.input name="city" :value="old('city', $draftValues['city'] ?? null)" :label="__('store.checkout.city')" required maxlength="100" />
                        <x-admin.input name="address_line" :value="old('address_line', $draftValues['address_line'] ?? null)" :label="__('store.checkout.address_line')" required maxlength="200" />
                        <x-admin.input name="building" :value="old('building', $draftValues['building'] ?? null)" :label="__('store.checkout.building')" maxlength="100" />
                    </div>
                </x-admin.card>

                <x-admin.card :title="__('admin.draft.delivery')">
                    <p x-show="! rates.length" class="text-[13px] text-muted">{{ __('admin.draft.delivery_empty') }}</p>
                    <div class="space-y-2">
                        <template x-for="r in rates" :key="r.code">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2.5 text-[13px] ring-1 ring-line has-[:checked]:bg-subdued has-[:checked]:ring-ink-strong">
                                <input type="radio" name="shipping_method" :value="r.code" x-model="shipping" class="accent-ink-strong">
                                <span class="flex-1" x-text="r.name"></span><span dir="ltr" x-text="r.price"></span>
                            </label>
                        </template>
                    </div>
                </x-admin.card>

                <x-admin.card :title="__('admin.draft.more')">
                    <div class="space-y-3">
                        <x-admin.textarea name="notes" :value="old('notes', $draftValues['notes'] ?? null)" :label="__('admin.orders.notes')" rows="2" maxlength="1000" />
                        <div>
                            <label for="locale" class="mb-1 block text-[13px] font-medium">{{ __('admin.draft.language') }}</label>
                            <select id="locale" name="locale" class="admin-field">
                                @foreach (app(\App\Support\Localization::class)->locales() as $code => $meta)
                                    <option value="{{ $code }}">{{ $meta['native'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="notify" value="1" checked class="admin-checkbox"> {{ __('admin.draft.notify') }}</label>
                    </div>
                </x-admin.card>
            </div>
        </div>

        <div class="mt-6 flex justify-end border-t border-line pt-4">
            <x-admin.button x-bind:disabled="! lines.length || ! shipping">{{ __('admin.draft.create') }}</x-admin.button>
            {{-- Saving a draft keeps everything entered so far without placing an order. --}}
            <x-admin.button type="submit" variant="secondary" formaction="{{ route('admin.draft-orders.save-draft') }}" formnovalidate>{{ __('admin.draft.save_draft') }}</x-admin.button>
            <a href="{{ route('admin.draft-orders.drafts') }}" class="text-[13px] text-muted underline">{{ __('admin.draft.drafts_title') }}</a>
        </div>
    </form>
@endsection
