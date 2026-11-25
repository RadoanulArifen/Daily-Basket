@extends('storefront.layouts.app')

{{-- Checkout — the design's three steps (Information → Shipping → Payment).
     One form throughout, so nothing is lost when moving between steps; the steps only
     show and hide. The delivery methods and payment options are the store's own. --}}
@section('furnivo', true)
@section('title', __('store.checkout.title'))
@section('noindex', true)

@php
    $field = fn ($name) => old($name, $defaults[$name] ?? null);
    $firstRate = array_key_first($rates ?? []);
    $firstGateway = array_key_first($gateways ?? []);
    // The summary is printed with the default choices, so it reads right before (or without) JavaScript.
    $shipDefault = old('shipping_method', $firstRate);
    $payDefault = old('payment_method', $firstGateway);
    $initial = $totalsMatrix[$shipDefault][$payDefault] ?? (($totalsMatrix[$firstRate] ?? [])[$firstGateway] ?? []);
    $currency = app(\App\Services\Currency\CurrencyService::class)->current();
    $settings = settings();
    // Send the shopper back to the first step that has a problem.
    $startStep = $errors->hasAny(['email', 'name', 'phone']) ? 1
        : ($errors->hasAny(['emirate', 'city', 'address_line', 'building', 'shipping_method']) ? 2 : ($errors->any() ? 3 : 1));
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $primary = 'height: 52px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;';
    $back = 'height: 52px; padding: 0px 26px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); color: rgb(32, 32, 32); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;';
    $errorText = 'margin: 8px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);';
@endphp

@section('content')
    <section style="width: 100%; padding: clamp(18px, 2vw, 28px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <a href="{{ store_route('cart') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.cart') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.theme.checkout') }}</span>
        </nav>
        <h1 style="margin: 0px 0px 34px; font-size: clamp(30px, 3.6vw, 44px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.theme.checkout') }}</h1>

        @if ($errors->any())
            <p role="alert" style="margin: 0px 0px 24px; padding: 14px 18px; border: 1px solid rgb(199, 64, 52); color: rgb(199, 64, 52); font-size: 14px;">{{ __('store.checkout.fix_errors') }}</p>
        @endif

        @if (! $rates || ! $gateways)
            <p role="status" style="margin: 0px; padding: 14px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 15px;">{{ __('store.checkout.unavailable') }}</p>
        @else
            <form method="POST" action="{{ store_route('checkout.store') }}" data-email-url="{{ store_route('checkout.email') }}" novalidate
                  x-data="checkoutForm({ step: {{ $startStep }}, shipping: @js($shipDefault), payment: @js($payDefault), totals: @js($totalsMatrix), coupon: {{ $summary->discount ? 'true' : 'false' }},
                            errorStyle: @js($errorText), messages: { required: @js(__('store.checkout.field_required')), email: @js(__('store.checkout.email_invalid')) } })"
                  @submit="submit($event)">
                @csrf

                {{-- Steps --}}
                <div style="display: flex; align-items: center; gap: 0px; margin: 0px auto 44px; max-width: 520px;">
                    @foreach (['Information', 'Shipping', 'Payment'] as $i => $name)
                        @php $number = $i + 1; @endphp
                        <div style="display: flex; align-items: center; flex: 1 1 0%;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 28px; height: 28px; border-radius: 50%; border: 1px solid rgb(29, 29, 29); display: flex; align-items: center; justify-content: center; font-size: 13px;"
                                      :style="step >= {{ $number }} ? { background: 'rgb(29, 29, 29)', color: 'rgb(255, 255, 255)', borderColor: 'rgb(29, 29, 29)' } : { background: 'transparent', color: 'rgb(139, 135, 127)', borderColor: 'rgb(220, 216, 210)' }"><span>{{ $number }}</span></span>
                                <span style="font-size: 13px; letter-spacing: 0.04em; white-space: nowrap;" :style="{ color: step >= {{ $number }} ? 'rgb(32, 32, 32)' : 'rgb(139, 135, 127)' }"><span>{{ $name }}</span></span>
                            </div>
                            @if ($number < 3)
                                <div style="flex: 1 1 0%; height: 1px; background: rgb(220, 216, 210); margin: 0px 14px;"></div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="cart-cols fx-cart-layout" style="display: grid; grid-template-columns: 1fr 360px; gap: clamp(30px, 5vw, 64px); align-items: start;">
                    <div style="min-width: 0px;">
                        {{-- 1. Information --}}
                        <div x-show="step === 1" data-step="1">
                            <h2 style="margin: 0px 0px 22px; font-size: 22px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.checkout.contact') }}</h2>
                            <div class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                                <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.auth.email') }}<x-store.required /></span>
                                    <input type="email" name="email" value="{{ $field('email') }}" autocomplete="email" required @blur="rememberEmail($event.target.value)" style="{{ $input }}">
                                </label>
                                @error('email')<p style="{{ $errorText }} grid-column: 1 / -1;">{{ $message }}</p>@enderror
                                <label style="{{ $label }}"><span>{{ __('store.checkout.full_name') }}<x-store.required /></span>
                                    <input name="name" value="{{ $field('name') }}" autocomplete="name" required style="{{ $input }}">
                                </label>
                                <label style="{{ $label }}"><span>{{ __('store.auth.phone') }}<x-store.required /></span>
                                    <input name="phone" value="{{ $field('phone') }}" autocomplete="tel" required dir="ltr" style="{{ $input }}">
                                </label>
                                @error('name')<p style="{{ $errorText }} grid-column: 1 / -1;">{{ $message }}</p>@enderror
                                @error('phone')<p style="{{ $errorText }} grid-column: 1 / -1;">{{ $message }}</p>@enderror
                            </div>
                            <button type="button" @click="go(2)" style="{{ $primary }} margin-top: 26px;">{{ __('store.theme.continue_shipping') }}</button>
                        </div>

                        {{-- 2. Shipping --}}
                        <div x-show="step === 2" x-cloak data-step="2">
                            <h2 style="margin: 0px 0px 22px; font-size: 22px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.checkout.address') }}</h2>
                            <div class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                                <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.checkout.address_line') }}<x-store.required /></span>
                                    <input name="address_line" value="{{ $field('address_line') }}" autocomplete="address-line1" required style="{{ $input }}">
                                </label>
                                <label style="{{ $label }}">{{ __('store.checkout.building') }}
                                    <input name="building" value="{{ $field('building') }}" autocomplete="address-line2" style="{{ $input }}">
                                </label>
                                <label style="{{ $label }}"><span>{{ __('store.checkout.city') }}<x-store.required /></span>
                                    <input name="city" value="{{ $field('city') }}" autocomplete="address-level2" required style="{{ $input }}">
                                </label>
                                <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.checkout.emirate') }}<x-store.required /></span>
                                    <select name="emirate" required autocomplete="address-level1" style="{{ $input }}">
                                        @foreach (config('store.emirates') as $emirate)
                                            <option value="{{ $emirate }}" @selected($field('emirate') === $emirate)>{{ __("store.emirates.{$emirate}") }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                @foreach (['address_line', 'building', 'city', 'emirate'] as $name)
                                    @error($name)<p style="{{ $errorText }} grid-column: 1 / -1;">{{ $message }}</p>@enderror
                                @endforeach
                            </div>

                            @auth('web')
                                <label style="display: flex; align-items: center; gap: 10px; margin-top: 18px; font-size: 14px; color: rgb(102, 102, 102); cursor: pointer;">
                                    <input type="checkbox" name="save_address" value="1" @checked(old('save_address', true)) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                                    {{ __('store.addresses.save_at_checkout') }}
                                </label>
                            @endauth

                            <div style="margin-top: 22px; display: flex; flex-direction: column; gap: 12px;">
                                <div style="font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102);">{{ __('store.checkout.delivery') }}</div>
                                @foreach ($rates as $code => $rate)
                                    <label style="cursor: pointer; display: flex; align-items: center; gap: 13px; padding: 14px 16px; transition: box-shadow 220ms;"
                                           :style="shipping === @js($code) ? { boxShadow: 'rgb(29, 29, 29) 0px 0px 0px 2px inset' } : { boxShadow: 'rgb(217, 213, 207) 0px 0px 0px 1px inset' }">
                                        <input type="radio" name="shipping_method" value="{{ $code }}" x-model="shipping" @checked(old('shipping_method', $firstRate) === $code) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                                        <span style="flex: 1 1 0%;">
                                            <span style="display: block; font-size: 15px; color: rgb(32, 32, 32);">{{ $rate->label() }}</span>
                                            @if ($rate->note())<span style="display: block; font-size: 13px; color: rgb(139, 135, 127);">{{ $rate->note() }}</span>@endif
                                        </span>
                                        <span style="font-size: 14px; color: rgb(32, 32, 32);">{{ $ratePrice($rate)->isZero() ? __('store.checkout.free') : price($ratePrice($rate)) }}</span>
                                    </label>
                                @endforeach
                                @error('shipping_method')<p style="{{ $errorText }}">{{ $message }}</p>@enderror
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 26px; flex-wrap: wrap;">
                                <button type="button" @click="go(1)" style="{{ $back }}">Back</button>
                                <button type="button" @click="go(3)" style="{{ $primary }}">{{ __('store.theme.continue_payment') }}</button>
                            </div>
                        </div>

                        {{-- 3. Payment --}}
                        <div x-show="step === 3" x-cloak data-step="3">
                            <h2 style="margin: 0px 0px 14px; font-size: 22px; font-weight: 600; letter-spacing: -0.01em;">{{ __('store.theme.select_payment') }}</h2>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgb(139, 135, 127); margin-bottom: 18px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="1.5"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                                {{ __('store.checkout.secure') }}
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 22px;">
                                @foreach ($gateways as $code => $gateway)
                                    <label style="cursor: pointer; display: flex; align-items: center; gap: 13px; padding: 14px 16px; transition: box-shadow 220ms;"
                                           :style="payment === @js($code) ? { boxShadow: 'rgb(29, 29, 29) 0px 0px 0px 2px inset' } : { boxShadow: 'rgb(217, 213, 207) 0px 0px 0px 1px inset' }">
                                        <input type="radio" name="payment_method" value="{{ $code }}" x-model="payment" @checked(old('payment_method', $firstGateway) === $code) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                                        <span style="flex: 1 1 0%; min-width: 0px;">
                                            <span style="display: block; font-size: 15px; color: rgb(32, 32, 32);">{{ $gateway->label() }}</span>
                                            @if ($text = $gateway->description())<span style="display: block; font-size: 13px; color: rgb(139, 135, 127);">{{ $text }}</span>@endif
                                        </span>
                                        @if (((float) $gateway->fee()) > 0)<span style="font-size: 12px; letter-spacing: 0.02em; color: rgb(139, 135, 127); white-space: nowrap;">{{ __('store.checkout.fee_note', ['fee' => price($gateway->fee())]) }}</span>@endif
                                    </label>
                                @endforeach
                                @error('payment_method')<p style="{{ $errorText }}">{{ $message }}</p>@enderror
                            </div>

                            <label style="{{ $label }} margin-bottom: 18px;">{{ __('store.checkout.notes') }}
                                <textarea name="notes" rows="3" maxlength="500" style="{{ $input }} resize: vertical;">{{ old('notes') }}</textarea>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 22px; font-size: 14px; color: rgb(102, 102, 102); cursor: pointer;">
                                <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in')) style="width: 16px; height: 16px; accent-color: rgb(29, 29, 29);">
                                {{ __('store.checkout.marketing') }}
                            </label>

                            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                                <button type="button" @click="go(2)" style="{{ $back }}">Back</button>
                                <button type="submit" :disabled="busy" style="{{ $primary }}"><span>{{ __('store.checkout.place_order') }}</span> · <span x-text="t.total_whole">{{ $initial['total_whole'] ?? '' }}</span></button>
                            </div>
                        </div>
                    </div>

                    {{-- Order summary --}}
                    <aside style="min-width: 0px; border: 1px solid rgb(236, 236, 236); padding: 26px;" aria-label="{{ __('store.cart.summary') }}">
                        <h2 style="margin: 0px 0px 20px; font-size: 16px; letter-spacing: 0.02em;">{{ __('store.theme.in_your_order') }}</h2>
                        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                            @foreach ($summary->lines as $line)
                                @php $media = $line->variant->image ?? $line->product->media->first(); @endphp
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <div style="width: 52px; height: 52px; overflow: hidden; background: rgb(239, 236, 231); position: relative; flex-shrink: 0;">
                                        @if ($media)<img src="{{ $media->url('thumb') }}" alt="{{ $line->product->name }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">@endif
                                    </div>
                                    <div style="flex: 1 1 0%; min-width: 0px; font-size: 14px;">
                                        <div style="color: rgb(32, 32, 32);"><span>{{ $line->product->name }}</span></div>
                                        <div style="color: rgb(102, 102, 102);">Qty <span>{{ $line->item->quantity }}</span></div>
                                        @if ($options = $line->item->optionsLabel())<div style="color: rgb(139, 135, 127); font-size: 12px; line-height: 1.45;">{{ $options }}</div>@endif
                                    </div>
                                    <div style="font-size: 14px; color: rgb(32, 32, 32);"><x-store.fv-price :money="$line->lineTotal" style="display: inline-flex; align-items: center;" /></div>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-bottom: 16px;">@include('storefront.partials.discount-form', ['formId' => 'checkout-discount'])</div>

                        <div style="display: flex; justify-content: space-between; font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 10px;">
                            <span>{{ __('store.cart.subtotal_plain') }}</span><span style="color: rgb(32, 32, 32);"><x-store.fv-price :money="$summary->discountedSubtotal()" style="display: inline-flex; align-items: center;" /></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 10px;">
                            <span>{{ __('store.cart.shipping') }}</span><span style="color: rgb(32, 32, 32);" x-text="t.shipping_whole">{{ $initial['shipping_whole'] ?? '' }}</span>
                        </div>
                        <div x-show="t.fee_whole" @if (empty($initial['fee_whole'])) x-cloak @endif>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 10px;">
                                <span>{{ __('store.checkout.cod_fee') }}</span><span style="color: rgb(32, 32, 32);" x-text="t.fee_whole">{{ $initial['fee_whole'] ?? '' }}</span>
                            </div>
                        </div>
                        <div x-show="t.tax_whole" @if (empty($initial['tax_whole'])) x-cloak @endif>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 10px;">
                                <span>{{ $settings->trans('tax.label') ?: __('store.checkout.payment') }}</span><span style="color: rgb(32, 32, 32);" x-text="t.tax_whole">{{ $initial['tax_whole'] ?? '' }}</span>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 17px; font-weight: 600; color: rgb(32, 32, 32); border-top: 1px solid rgb(236, 236, 236); padding-top: 16px; margin-top: 6px;">
                            <span>{{ __('store.cart.total') }}</span><span x-text="t.total_whole">{{ $initial['total_whole'] ?? '' }}</span>
                        </div>
                        @if ($currency->code !== config('store.base_currency'))
                            <p style="margin: 10px 0px 0px; font-size: 12px; color: rgb(139, 135, 127);">{{ __('store.cart.currency_note', ['currency' => $currency->code, 'base' => config('store.base_currency')]) }}</p>
                        @endif
                    </aside>
                </div>
            </form>
            @include('storefront.partials.discount-forms-outer', ['formId' => 'checkout-discount'])
        @endif
    </section>
@endsection
