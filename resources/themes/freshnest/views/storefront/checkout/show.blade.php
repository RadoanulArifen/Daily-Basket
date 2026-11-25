@extends('storefront.layouts.app')

{{--
    Checkout in the FreshNest look (the design has no checkout page). Three steps —
    Contact → Delivery → Payment — in one form, so nothing is lost between steps. Delivery
    methods, payment options (card via Stripe, cash on delivery), totals and codes are the
    store's own (Settings → Shipping / Payments, Marketing → Discounts).
--}}
@section('freshnest', true)
@section('title', __('Checkout'))
@section('noindex', true)

@php
    $field = fn ($name) => old($name, $defaults[$name] ?? null);
    $firstRate = array_key_first($rates ?? []);
    $firstGateway = array_key_first($gateways ?? []);
    // The summary is printed with the default choices, so it reads right before (or without) JavaScript.
    $shipDefault = old('shipping_method', $firstRate);
    $payDefault = old('payment_method', $firstGateway);
    $initial = $totalsMatrix[$shipDefault][$payDefault] ?? (($totalsMatrix[$firstRate] ?? [])[$firstGateway] ?? []);
    $plain = fn ($v) => preg_replace('/^([^\d\s]+)\x{00A0}/u', '$1', (string) $v);
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $settings = settings();
    $startStep = $errors->hasAny(['email', 'name', 'phone']) ? 1
        : ($errors->hasAny(['country', 'state', 'city', 'address_line', 'building', 'shipping_method']) ? 2 : ($errors->any() ? 3 : 1));
    $arrow = '<span class="ck-btn-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>';
@endphp

@section('content')
    @include('storefront.partials.page-band', ['title' => __('Checkout'), 'crumbs' => [[__('Cart'), store_route('cart')]], 'blurb' => 'Three short steps. You can change anything before you place the order.'])

    <div class="wrap" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px);">
        @if ($errors->any())
            <p class="ck-alert" role="alert">{{ __('store.checkout.fix_errors') }}</p>
        @endif

        @if (! $rates || ! $gateways)
            <div class="ck-card"><p role="status" style="margin:0; font-size:15px; color:var(--fn-muted);">{{ __('store.checkout.unavailable') }}</p></div>
        @else
            <form method="POST" action="{{ store_route('checkout.store') }}" data-email-url="{{ store_route('checkout.email') }}" novalidate
                  x-data="checkoutForm({ step: {{ $startStep }}, shipping: @js($shipDefault), payment: @js($payDefault), totals: @js($totalsMatrix), errorClass: 'ck-error',
                            messages: { required: @js(__('store.checkout.field_required')), email: @js(__('store.checkout.email_invalid')) } })"
                  @submit="submit($event)">
                @csrf

                <div class="ck-steps" aria-label="{{ __('Steps') }}">
                    @foreach (['Contact', 'Delivery', 'Payment'] as $i => $name)
                        <div class="ck-step {{ $i === 0 ? 'is-on' : '' }}" :class="step >= {{ $i + 1 }} ? 'is-on' : ''">
                            <span class="ck-step-n">{{ $i + 1 }}</span><span>{{ $name }}</span>
                        </div>
                        @if ($i < 2)<span class="ck-line" aria-hidden="true"></span>@endif
                    @endforeach
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:24px; align-items:flex-start;">
                    <div style="flex:2 1 520px; min-width:0;">
                        {{-- 1. Contact --}}
                        <div x-show="step === 1" data-step="1">
                            <div class="ck-card">
                                <h2 class="ck-h">{{ __('store.checkout.contact') }}</h2>
                                <div class="ck-grid">
                                    <label class="ck-label ck-wide"><span>{{ __('store.auth.email') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <input class="ck-input" type="email" name="email" value="{{ $field('email') }}" autocomplete="email" required @blur="rememberEmail($event.target.value)">
                                        @error('email')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.checkout.full_name') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <input class="ck-input" name="name" value="{{ $field('name') }}" autocomplete="name" required>
                                        @error('name')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.auth.phone') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <input class="ck-input" name="phone" value="{{ $field('phone') }}" autocomplete="tel" required dir="ltr">
                                        @error('phone')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                </div>
                                <div class="ck-actions" style="justify-content:flex-end;">
                                    <button type="button" class="ck-btn" @click="go(2)"><span>{{ __('Continue to delivery') }}</span>{!! $arrow !!}</button>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Delivery --}}
                        <div x-show="step === 2" x-cloak data-step="2">
                            <div class="ck-card">
                                <h2 class="ck-h">{{ __('store.checkout.address') }}</h2>
                                <div class="ck-grid">
                                    <label class="ck-label ck-wide"><span>{{ __('store.checkout.address_line') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <input class="ck-input" name="address_line" value="{{ $field('address_line') }}" autocomplete="address-line1" required>
                                        @error('address_line')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.checkout.building') }}</span>
                                        <input class="ck-input" name="building" value="{{ $field('building') }}" autocomplete="address-line2">
                                        @error('building')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.checkout.city') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <input class="ck-input" name="city" value="{{ $field('city') }}" autocomplete="address-level2" required>
                                        @error('city')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.checkout.country') }} <span class="ck-req" aria-hidden="true">*</span></span>
                                        <select class="ck-input" name="country" required autocomplete="country">
                                            @foreach (\App\Support\Countries::allowed() as $code => $countryName)
                                                <option value="{{ $code }}" @selected(($field('country') ?: \App\Support\Countries::default()) === $code)>{{ $countryName }}</option>
                                            @endforeach
                                        </select>
                                        @error('country')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                    <label class="ck-label"><span>{{ __('store.checkout.state') }}</span>
                                        <input class="ck-input" name="state" value="{{ $field('state') }}" autocomplete="address-level1" placeholder="{{ __('store.checkout.state_hint') }}">
                                        @error('state')<p class="ck-error">{{ $message }}</p>@enderror
                                    </label>
                                </div>
                                @auth('web')
                                    <label class="ck-check"><input type="checkbox" name="save_address" value="1" @checked(old('save_address', true))>{{ __('store.addresses.save_at_checkout') }}</label>
                                @endauth

                                <h2 class="ck-h" style="margin-top:6px;">{{ __('store.checkout.delivery') }}</h2>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    @foreach ($rates as $code => $rate)
                                        <label class="ck-choice {{ old('shipping_method', $firstRate) === $code ? 'is-on' : '' }}" :class="shipping === @js($code) ? 'is-on' : ''">
                                            <input type="radio" name="shipping_method" value="{{ $code }}" x-model="shipping" @checked(old('shipping_method', $firstRate) === $code)>
                                            <span style="flex:1 1 auto;"><span class="ck-choice-t">{{ $rate->label() }}</span>@if ($rate->note())<span class="ck-choice-n">{{ $rate->note() }}</span>@endif</span>
                                            <strong style="font-size:15px; color:var(--fn-primary);">{{ $ratePrice($rate)->isZero() ? __('store.checkout.free') : $money($ratePrice($rate)) }}</strong>
                                        </label>
                                    @endforeach
                                    @error('shipping_method')<p class="ck-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="ck-actions">
                                    <button type="button" class="ck-back" @click="go(1)">{{ __('Back') }}</button>
                                    <button type="button" class="ck-btn" @click="go(3)"><span>{{ __('Continue to payment') }}</span>{!! $arrow !!}</button>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Payment --}}
                        <div x-show="step === 3" x-cloak data-step="3">
                            <div class="ck-card">
                                <h2 class="ck-h">{{ __('store.checkout.payment') }}</h2>
                                <div class="ck-secure"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>{{ __('store.checkout.secure') }}</div>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    @foreach ($gateways as $code => $gateway)
                                        <label class="ck-choice {{ old('payment_method', $firstGateway) === $code ? 'is-on' : '' }}" :class="payment === @js($code) ? 'is-on' : ''">
                                            <input type="radio" name="payment_method" value="{{ $code }}" x-model="payment" @checked(old('payment_method', $firstGateway) === $code)>
                                            <span style="flex:1 1 auto;"><span class="ck-choice-t">{{ $gateway->label() }}</span>@if ($text = $gateway->description())<span class="ck-choice-n">{{ $text }}</span>@endif</span>
                                            @if (((float) $gateway->fee()) > 0)<span class="ck-choice-n">{{ __('store.checkout.fee_note', ['fee' => price($gateway->fee())]) }}</span>@endif
                                        </label>
                                    @endforeach
                                    @error('payment_method')<p class="ck-error">{{ $message }}</p>@enderror
                                </div>
                                <label class="ck-label"><span>{{ __('store.checkout.notes') }}</span>
                                    <textarea class="ck-input" name="notes" rows="3" maxlength="500" placeholder="{{ __('Gate code, safe place for chilled items…') }}">{{ old('notes') }}</textarea>
                                </label>
                                <label class="ck-check"><input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))>{{ __('Email me harvest notes and offers') }}</label>
                                <div class="ck-actions">
                                    <button type="button" class="ck-back" @click="go(2)">{{ __('Back') }}</button>
                                    <button type="submit" class="ck-btn" :disabled="busy" style="background:var(--fn-accent);"><span>{{ __('store.checkout.place_order') }} · <span x-text="fmt(t.total)">{{ $plain($initial['total'] ?? '') }}</span></span><span class="ck-btn-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Order summary --}}
                    <aside aria-label="{{ __('store.cart.summary') }}" class="ck-card" style="flex:1 1 320px; min-width:0;">
                        <h2 class="ck-h">{{ __('Order summary') }}</h2>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            @foreach ($summary->lines as $line)
                                @php $media = $line->variant->image ?? $line->product->media->first(); $size = $line->variant->title(); @endphp
                                <div style="display:flex; gap:12px; align-items:center;">
                                    <span style="position:relative; flex:0 0 auto; width:56px; height:56px; border-radius:12px; background:var(--fn-primary-tint){{ $media ? ' url('.$media->url('thumb').')' : '' }} center/cover no-repeat;">
                                        <span style="position:absolute; top:-6px; right:-6px; min-width:20px; height:20px; padding:0 5px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center;">{{ $line->item->quantity }}</span>
                                    </span>
                                    <span style="flex:1 1 auto; min-width:0; line-height:1.35;">
                                        <strong style="display:block; font-size:14.5px; color:var(--fn-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $line->product->name }}</strong>
                                        <span style="font-size:12.5px; color:var(--fn-muted);">{{ $size }}{{ ($opt = $line->item->optionsLabel()) ? ' · '.$opt : '' }}</span>
                                    </span>
                                    <strong style="font-size:14.5px; color:var(--fn-ink);">{{ $money($line->lineTotal) }}</strong>
                                </div>
                            @endforeach
                        </div>

                        @if ($summary->discount)
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:13.5px; font-weight:600; color:var(--fn-primary-dark); background:var(--fn-primary-tint); padding:10px 12px; border-radius:10px;">
                                <span>✓ {{ $summary->discount->code }} {{ __('applied') }}</span>
                                <button type="submit" form="checkout-discount-remove" style="border:0; background:transparent; color:var(--fn-muted); font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:underline;">{{ __('store.discount.remove') }}</button>
                            </div>
                        @else
                            <div class="ck-code">
                                <input form="checkout-discount" name="code" maxlength="40" autocomplete="off" value="{{ old('code') }}" placeholder="{{ __('Coupon code') }}" aria-label="{{ __('store.discount.label') }}" dir="ltr">
                                <button type="submit" form="checkout-discount">{{ __('store.discount.apply') }}</button>
                            </div>
                            @error('code')<p class="ck-error" role="alert">{{ $message }}</p>@enderror
                        @endif
                        @error('discount')<p class="ck-error" role="alert">{{ $message }}</p>@enderror

                        <div class="ck-row"><span>{{ __('Subtotal') }}</span><span>{{ $money($summary->subtotal) }}</span></div>
                        @foreach ($summary->promotions as $deal)
                            <div class="ck-row"><span>{{ __('store.deals.line', ['name' => $deal['name'][app()->getLocale()] ?? $deal['name']['en']]) }}</span><span style="color:var(--fn-primary) !important;">−{{ $money($deal['amount']) }}</span></div>
                        @endforeach
                        @if ($summary->discount && $summary->discountAmount && ! $summary->discountAmount->isZero())
                            <div class="ck-row"><span>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</span><span style="color:var(--fn-primary) !important;">−{{ $money($summary->discountAmount) }}</span></div>
                        @endif
                        <div class="ck-row"><span>{{ __('Delivery') }}@if ($summary->freeShipping()) <span style="color:var(--fn-primary) !important;">({{ $summary->discount->code }})</span>@endif</span><span x-text="fmt(t.shipping)">{{ $plain($initial['shipping'] ?? '') }}</span></div>
                        <div x-show="t.fee" @if (empty($initial['fee'])) x-cloak @endif><div class="ck-row"><span>{{ __('store.checkout.cod_fee') }}</span><span x-text="fmt(t.fee)">{{ $plain($initial['fee'] ?? '') }}</span></div></div>
                        @if ($settings->bool('tax.enabled'))
                            <div class="ck-row"><span>{{ $settings->trans('tax.label') ?: __('Tax') }}</span><span x-text="fmt(t.tax)">{{ $plain($initial['tax'] ?? '') }}</span></div>
                        @endif
                        <div class="ck-total"><span>{{ __('Total') }}</span><span x-text="fmt(t.total)">{{ $plain($initial['total'] ?? '') }}</span></div>
                    </aside>
                </div>
            </form>
            @include('storefront.partials.discount-forms-outer', ['formId' => 'checkout-discount'])
        @endif
    </div>
@endsection
