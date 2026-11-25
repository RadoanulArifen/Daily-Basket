@extends('storefront.layouts.app')

@section('title', __('store.checkout.title'))
@section('noindex', true)

@php
    $field = fn ($name) => old($name, $defaults[$name] ?? null);
    $firstRate = array_key_first($rates);
    $firstGateway = array_key_first($gateways);
    $currency = app(\App\Services\Currency\CurrencyService::class)->current();
    $settings = settings();
@endphp

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-8 sm:px-6 lg:px-10">
        <x-store.breadcrumbs :items="[['label' => __('store.cart.title'), 'url' => store_route('cart')], ['label' => __('store.checkout.title')]]" />
        <h1 class="mt-6 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.checkout.title') }}</h1>

        @if ($errors->any())
            <x-store.alert tone="error" class="mt-6">{{ __('store.checkout.fix_errors') }}</x-store.alert>
        @endif

        @if (! $rates || ! $gateways)
            <x-store.alert tone="warning" class="mt-6">{{ __('store.checkout.unavailable') }}</x-store.alert>
        @else
        <form method="POST" action="{{ store_route('checkout.store') }}" data-email-url="{{ store_route('checkout.email') }}" class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14"
              x-data="{ shipping: @js(old('shipping_method', $firstRate)), payment: @js(old('payment_method', $firstGateway)), totals: @js($totalsMatrix), busy: false,
                        get t() { return this.totals[this.shipping]?.[this.payment] ?? {}; },
                        rememberEmail(value) {
                            if (! /.+@.+\..+/.test(value)) return;
                            fetch(this.$root.dataset.emailUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ email: value }) }).catch(() => {});
                        } }"
              @submit="busy = true" novalidate>
            @csrf

            <div class="space-y-10 lg:col-span-7">
                {{-- Contact --}}
                <section aria-labelledby="contact-heading">
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 id="contact-heading" class="text-lg font-semibold">{{ __('store.checkout.contact') }}</h2>
                        @guest('web')
                            <p class="text-sm text-ink-soft">{{ __('store.checkout.have_account') }} <a href="{{ store_route('login') }}" class="font-medium text-ink underline underline-offset-4">{{ __('store.auth.login') }}</a></p>
                        @endguest
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-store.input name="email" type="email" :label="__('store.auth.email')" :value="$field('email')" autocomplete="email" required class="sm:col-span-2" x-on:change="rememberEmail($el.value)" />
                        <x-store.input name="phone" type="tel" :label="__('store.auth.phone')" :value="$field('phone')" autocomplete="tel" :required="$settings->bool('checkout.require_phone', true)" dir="ltr" placeholder="+971 50 123 4567" class="sm:col-span-2" :help="__('store.checkout.phone_help')" />
                    </div>
                    @if ($settings->bool('checkout.marketing_opt_in', true))
                        <label class="mt-4 flex items-center gap-3 text-[15px]">
                            <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in')) class="size-4 accent-ink">
                            {{ __('store.checkout.marketing') }}
                        </label>
                    @endif
                </section>

                {{-- Address --}}
                <section aria-labelledby="address-heading" x-data="{
                        saved: @js($savedAddresses->map->toCheckoutArray()->values()),
                        picked: @js(old('address_line') ? null : $savedAddresses->first()?->id),
                        pick(id) {
                            this.picked = id;
                            const a = this.saved.find((x) => x.id === id);
                            if (! a) return;
                            ['name', 'phone', 'country', 'state', 'city', 'address_line', 'building'].forEach((f) => { const el = document.getElementById(f); if (el) el.value = a[f] ?? ''; });
                        },
                    }">
                    <h2 id="address-heading" class="text-lg font-semibold">{{ __('store.checkout.address') }}</h2>
                    @if ($savedAddresses->isNotEmpty())
                        <div class="mt-4 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('store.addresses.saved_label') }}">
                            @foreach ($savedAddresses as $saved)
                                <button type="button" @click="pick({{ $saved->id }})" role="radio" :aria-checked="picked === {{ $saved->id }}"
                                        class="rounded-xl border p-4 text-start text-sm transition" :class="picked === {{ $saved->id }} ? 'border-ink bg-wool-soft/60' : 'border-line hover:border-stone'">
                                    <span class="block font-medium">{{ $saved->label ?: $saved->name }}</span>
                                    <span class="mt-1 block text-ink-soft">{{ $saved->address_line }}, {{ $saved->regionLine() }}</span>
                                </button>
                            @endforeach
                            <button type="button" @click="picked = 'new'; ['city', 'address_line', 'building'].forEach((f) => document.getElementById(f).value = '')"
                                    class="rounded-xl border border-dashed p-4 text-start text-sm" :class="picked === 'new' ? 'border-ink' : 'border-line hover:border-stone'">
                                <span class="flex items-center gap-2 font-medium"><x-icon name="plus" class="size-4" /> {{ __('store.addresses.use_new') }}</span>
                            </button>
                        </div>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-store.input name="name" :label="__('store.checkout.full_name')" :value="$field('name')" autocomplete="name" required class="sm:col-span-2" />
                        <div class="space-y-1.5">
                            <label for="country" class="block text-[15px] font-medium">{{ __('store.checkout.country') }}</label>
                            <select id="country" name="country" required autocomplete="country" class="store-field @error('country') store-field-error @enderror">
                                @foreach (\App\Support\Countries::allowed() as $code => $countryName)
                                    <option value="{{ $code }}" @selected(($field('country') ?: \App\Support\Countries::default()) === $code)>{{ $countryName }}</option>
                                @endforeach
                            </select>
                            @error('country')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-store.input name="state" :label="__('store.checkout.state')" :value="$field('state')" autocomplete="address-level1" :placeholder="__('store.checkout.state_hint')" />
                        <x-store.input name="city" :label="__('store.checkout.city')" :value="$field('city')" autocomplete="address-level2" required :placeholder="__('store.checkout.city_placeholder')" />
                        <x-store.input name="address_line" :label="__('store.checkout.address_line')" :value="$field('address_line')" autocomplete="street-address" required :placeholder="__('store.checkout.address_placeholder')" class="sm:col-span-2" />
                        <x-store.input name="building" :label="__('store.checkout.building')" :value="$field('building')" class="sm:col-span-2" :placeholder="__('store.checkout.building_placeholder')" />
                    </div>
                    <p class="mt-3 text-sm text-stone">{{ __('store.checkout.uae_only') }}</p>
                    @auth('web')
                        <label class="mt-4 flex items-center gap-3 text-[15px]" @if ($savedAddresses->isNotEmpty()) x-show="picked === 'new' || picked === null" @endif>
                            <input type="hidden" name="save_address" value="0">
                            <input type="checkbox" name="save_address" value="1" @checked(old('save_address', true)) class="size-4 accent-ink">
                            {{ __('store.addresses.save_at_checkout') }}
                        </label>
                    @endauth
                </section>

                {{-- Delivery --}}
                <section aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading" class="text-lg font-semibold">{{ __('store.checkout.delivery') }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($rates as $code => $rate)
                            <label class="flex cursor-pointer items-center gap-4 rounded-md border p-4 transition" :class="shipping === @js($code) ? 'border-ink bg-wool-soft/60' : 'border-line hover:border-stone'">
                                <input type="radio" name="shipping_method" value="{{ $code }}" x-model="shipping" class="size-4 accent-ink" @checked(old('shipping_method', $firstRate) === $code)>
                                <span class="flex-1">
                                    <span class="block text-[15px] font-medium">{{ $rate->label() }}</span>
                                    @if ($rate->note())<span class="block text-sm text-ink-soft">{{ $rate->note() }}</span>@endif
                                </span>
                                <span class="text-[15px] font-medium">{{ $ratePrice($rate)->isZero() ? __('store.checkout.free') : price($ratePrice($rate)) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('shipping_method')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                </section>

                {{-- Payment --}}
                <section aria-labelledby="payment-heading">
                    <h2 id="payment-heading" class="text-lg font-semibold">{{ __('store.checkout.payment') }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($gateways as $code => $gateway)
                            <label class="flex cursor-pointer items-start gap-4 rounded-md border p-4 transition" :class="payment === @js($code) ? 'border-ink bg-wool-soft/60' : 'border-line hover:border-stone'">
                                <input type="radio" name="payment_method" value="{{ $code }}" x-model="payment" class="mt-1 size-4 accent-ink" @checked(old('payment_method', $firstGateway) === $code)>
                                <span class="flex-1">
                                    <span class="flex items-center gap-2 text-[15px] font-medium">
                                        <x-icon :name="$code === 'cod' ? 'coins' : 'credit-card'" class="size-5 text-stone" /> {{ $gateway->label() }}
                                    </span>
                                    @if ($gateway->description())<span class="mt-1 block text-sm text-ink-soft">{{ $gateway->description() }}</span>@endif
                                    @if ((float) $gateway->fee() > 0)<span class="mt-1 block text-sm text-ink-soft">{{ __('store.checkout.fee_note', ['fee' => price($gateway->fee())]) }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                </section>

                @if ($settings->bool('checkout.order_notes', true))
                    <section>
                        <label for="notes" class="text-[15px] font-medium">{{ __('store.checkout.notes') }} <span class="font-normal text-stone">({{ __('store.common.optional') }})</span></label>
                        <textarea id="notes" name="notes" rows="3" maxlength="500" class="store-field mt-2" placeholder="{{ __('store.checkout.notes_placeholder') }}">{{ old('notes') }}</textarea>
                    </section>
                @endif
            </div>

            {{-- Summary --}}
            <aside class="lg:col-span-5" aria-labelledby="summary-heading">
                <div class="rounded-md bg-wool-soft p-6 lg:sticky lg:top-28">
                    <h2 id="summary-heading" class="text-lg font-semibold">{{ __('store.cart.summary') }}</h2>
                    <ul class="mt-5 space-y-4">
                        @foreach ($summary->lines as $line)
                            @php $image = $line->variant->image ?? $line->product->media->first(); @endphp
                            <li class="flex items-center gap-4">
                                <span class="relative shrink-0">
                                    @if ($image)<img src="{{ $image->url('thumb') }}" alt="" class="aspect-[4/5] w-14 rounded-sm object-cover">@else<span class="block aspect-[4/5] w-14 rounded-sm bg-wool"></span>@endif
                                    <span class="absolute -end-2 -top-2 flex size-5 items-center justify-center rounded-full bg-ink text-[11px] text-paper">{{ $line->item->quantity }}</span>
                                </span>
                                <span class="min-w-0 flex-1 text-[15px]">
                                    <span class="block truncate">{{ $line->product->name }}</span>
                                    @if ($title = $line->variant->title())<span class="block text-sm text-ink-soft">{{ $title }}</span>@endif
                                    @if ($line->item->optionsLabel())<span class="block text-sm text-ink-soft">{{ $line->item->optionsLabel() }}</span>@endif
                                </span>
                                <span class="text-[15px]">{{ price($line->lineTotal) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mt-6 space-y-2.5 border-t border-line pt-5 text-[15px]">
                        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('store.cart.subtotal_plain') }}</dt><dd>{{ price($summary->subtotal) }}</dd></div>
                        @foreach ($summary->promotions as $deal)
                            <div class="flex justify-between gap-4 text-brand-red"><dt>{{ __('store.deals.line', ['name' => $deal['name'][app()->getLocale()] ?? $deal['name']['en']]) }}</dt><dd>−{{ price($deal['amount']) }}</dd></div>
                        @endforeach
                        @if ($summary->discount && ! $summary->freeShipping())
                            <div class="flex justify-between gap-4 text-brand-red"><dt>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</dt><dd>−{{ price($summary->discountAmount) }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('store.cart.shipping') }}@if ($summary->freeShipping()) <span class="text-brand-red">({{ $summary->discount->code }})</span>@endif</dt><dd x-text="t.shipping"></dd></div>
                        <div class="flex justify-between gap-4" x-show="t.fee" x-cloak><dt class="text-ink-soft">{{ __('store.checkout.cod_fee') }}</dt><dd x-text="t.fee"></dd></div>
                    </dl>
                    <div class="mt-5">@include('storefront.partials.discount-form', ['formId' => 'checkout-discount'])</div>
                    <div class="mt-5 flex justify-between gap-4 border-t border-line pt-5 text-lg font-semibold">
                        <span>{{ __('store.cart.total') }}</span><span x-text="t.total">{{ price($initialTotals->grandTotal) }}</span>
                    </div>
                    @if ($settings->bool('tax.enabled'))
                        <p class="mt-1 text-sm text-stone">
                            {{ $settings->bool('tax.prices_include_tax', true) ? __('store.checkout.tax_included_amount', ['tax' => $settings->trans('tax.label')]) : __('store.checkout.tax_added_amount', ['tax' => $settings->trans('tax.label')]) }}
                            <span x-text="t.tax"></span>
                        </p>
                    @endif
                    @if ($currency->code !== config('store.base_currency'))
                        <p class="mt-3 text-sm text-stone">{{ __('store.cart.currency_note', ['currency' => $currency->code, 'base' => config('store.base_currency')]) }}</p>
                    @endif

                    <x-store.button class="mt-6 w-full" x-bind:disabled="busy">
                        <span x-show="! busy" x-text="payment === 'cod' ? @js(__('store.checkout.place_order')) : @js(__('store.checkout.continue_to_payment'))">{{ __('store.checkout.place_order') }}</span>
                        <span x-show="busy" x-cloak>{{ __('store.checkout.placing') }}</span>
                    </x-store.button>
                    <p class="mt-3 flex items-center justify-center gap-2 text-center text-sm text-stone"><x-icon name="lock" class="size-4" /> {{ __('store.checkout.secure') }}</p>
                </div>
            </aside>
        </form>
        @include('storefront.partials.discount-forms-outer', ['formId' => 'checkout-discount'])
        @endif
    </div>
@endsection
