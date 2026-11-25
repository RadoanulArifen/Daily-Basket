@extends('storefront.account.layout')

{{-- Add / edit an address, in the design's form style. --}}
@section('title', $address->exists ? __('Edit address') : 'Add a new address')
@section('account_tab', 'addresses')

@section('account')
@php $v = fn ($name) => old($name, $address->{$name}); @endphp
            <form method="POST" action="{{ $address->exists ? store_route('account.addresses.update', ['address' => $address->id]) : store_route('account.addresses.store') }}" style="display:flex; flex-direction:column; gap:18px; padding:clamp(20px,2.6vw,30px); background:#FFFFFF; border:1px solid var(--fn-line); border-radius:22px;">
              @csrf
              @if ($address->exists) @method('PUT') @endif
              <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:20px; margin:0;">{{ $address->exists ? __('Edit address') : __('Add a new address') }}</h2>
              <div class="ck-grid">
                <label class="ck-label"><span>{{ __('Label') }}</span><input class="ck-input" name="label" value="{{ $v('label') }}" maxlength="40" placeholder="{{ __('Home, Work…') }}">@error('label')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.checkout.full_name') }} <span class="ck-req">*</span></span><input class="ck-input" name="name" value="{{ $v('name') }}" required autocomplete="name">@error('name')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label ck-wide"><span>{{ __('store.checkout.address_line') }} <span class="ck-req">*</span></span><input class="ck-input" name="address_line" value="{{ $v('address_line') }}" required autocomplete="address-line1">@error('address_line')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.checkout.building') }}</span><input class="ck-input" name="building" value="{{ $v('building') }}" autocomplete="address-line2">@error('building')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.checkout.city') }} <span class="ck-req">*</span></span><input class="ck-input" name="city" value="{{ $v('city') }}" required autocomplete="address-level2">@error('city')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.checkout.country') }} <span class="ck-req">*</span></span>
                  <select class="ck-input" name="country" required autocomplete="country">@foreach (\App\Support\Countries::allowed() as $code => $countryName)<option value="{{ $code }}" @selected(($v('country') ?: \App\Support\Countries::default()) === $code)>{{ $countryName }}</option>@endforeach</select>
                  @error('country')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.checkout.state') }}</span><input class="ck-input" name="state" value="{{ $v('state') }}" autocomplete="address-level1" placeholder="{{ __('store.checkout.state_hint') }}">@error('state')<p class="ck-error">{{ $message }}</p>@enderror</label>
                <label class="ck-label"><span>{{ __('store.auth.phone') }} <span class="ck-req">*</span></span><input class="ck-input" name="phone" value="{{ $v('phone') }}" required dir="ltr" autocomplete="tel">@error('phone')<p class="ck-error">{{ $message }}</p>@enderror</label>
              </div>
              <label class="ck-check"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>{{ __('Use as my default address') }}</label>
              <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="fh-92c14d" type="submit" style="height:48px; padding:0 26px; border:0; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14.5px; cursor:pointer;">{{ __('Save address') }}</button>
                <a href="{{ store_route('account.addresses') }}" style="height:48px; padding:0 22px; border-radius:999px; border:1.5px solid var(--fn-primary-pale); color:var(--fn-primary-dark); font-weight:700; font-size:14.5px; display:inline-flex; align-items:center;">{{ __('Cancel') }}</a>
              </div>
            </form>
@endsection
