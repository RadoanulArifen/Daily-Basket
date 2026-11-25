@extends('storefront.account.layout')

{{-- My Profile: the design's four figures and personal details. The platform has no
     "edit profile" form yet, so the details are shown, not edited. --}}
@section('title', __('My Profile'))
@section('account_tab', 'profile')

@section('account')
@php
    $customer ??= auth('web')->user();
    $orders = $customer->orders();
    $spent = \App\Models\Order::query()->where('user_id', $customer->id)->get()->reduce(fn ($sum, $o) => $sum ? $sum->add($o->grand_total) : $o->grand_total);
    $stats = [
        [$customer->orders()->count(), __('Total orders'), 'var(--fn-primary-tint)', 'var(--fn-primary)', 'M6 3h12l1 5H5ZM5 8v12h14V8'],
        [$spent ? \App\Themes\Freshnest\Price::show($spent) : \App\Themes\Freshnest\Price::show(\App\Support\Money::zero(config('store.base_currency'))), __('Total spent'), 'var(--fn-accent-tint)', 'var(--fn-accent)', 'M12 3v18M16.5 7.5c0-1.9-2-3-4.5-3s-4.5 1.1-4.5 3 2 2.6 4.5 3 4.5 1.1 4.5 3-2 3-4.5 3-4.5-1.1-4.5-3'],
        [app(\App\Services\Wishlist\WishlistService::class)->count(), __('Wishlist items'), 'var(--fn-primary-tint)', 'var(--fn-primary)', 'M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20Z'],
        [$customer->addresses()->count(), __('Saved addresses'), 'var(--fn-accent-tint)', 'var(--fn-accent)', 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z'],
    ];
    $field = 'height:48px; padding:0 14px; border:1px solid #DFE6DF; border-radius:12px; font-size:14.5px; color:var(--fn-ink); background:var(--fn-canvas); display:flex; align-items:center;';
@endphp
            <div class="stat4">
              @foreach ($stats as [$v, $l, $bg, $c, $icon])
                <div style="display:flex; align-items:center; gap:14px; padding:18px; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px;">
                  <span style="display:inline-flex; width:46px; height:46px; flex:0 0 auto; border-radius:14px; background:{{ $bg }}; align-items:center; justify-content:center;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $c }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"></path></svg></span>
                  <span style="line-height:1.3;"><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:22px;">{{ $v }}</strong><span style="font-size:13px; color:var(--fn-muted);">{{ $l }}</span></span>
                </div>
              @endforeach
            </div>
            <div style="display:flex; flex-direction:column; gap:18px; padding:clamp(20px,2.6vw,30px); background:#FFFFFF; border:1px solid var(--fn-line); border-radius:22px;">
              <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:20px; margin:0;">{{ __('Personal information') }}</h2>
              <div class="f2">
                @foreach (['Full name' => $customer->name, 'Email address' => $customer->email, 'Phone number' => $customer->phone ?? '—', 'Member since' => $customer->created_at?->format('F Y')] as $label => $value)
                  <div style="display:flex; flex-direction:column; gap:7px; font-size:13px; font-weight:600; color:#4F5B4F;">{{ __($label) }}<span style="{{ $field }}">{{ $value ?: '—' }}</span></div>
                @endforeach
              </div>
              <p style="margin:0; font-size:13.5px; color:var(--fn-muted);">{{ __('To change these details, contact us at') }} <a href="mailto:{{ settings()->get('general.store_email') }}">{{ settings()->get('general.store_email') }}</a>.</p>
            </div>
@endsection
