@extends('storefront.layouts.app')

{{-- Account pages from the design (account.html …): green band, member card and tab menu on
     the left, the tab's content on the right. The design's "Account Settings" tab and
     "Green Member" badge have no store feature behind them and are left out. --}}
@section('freshnest', true)
@section('noindex', true)

@section('content')
@php
    $customer = auth('web')->user();
    $initials = collect(preg_split('/\s+/', trim((string) $customer->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $tab = trim($__env->yieldContent('account_tab'));
    $tabs = [
        ['profile', __('My Profile'), store_route('account.dashboard'), 'M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4.5 20.5c1.4-3.6 4.2-5.5 7.5-5.5s6.1 1.9 7.5 5.5', null],
        ['orders', __('My Orders'), store_route('account.orders'), 'M6 3h12l1 5H5ZM5 8v12h14V8M9 12h6', $customer->orders()->count()],
        ['wishlist', __('Wishlist'), store_route('wishlist'), 'M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20Z', app(\App\Services\Wishlist\WishlistService::class)->count()],
        ['addresses', __('Saved Addresses'), store_route('account.addresses'), 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21ZM12 7a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z', null],
    ];
    $pageTitle = collect($tabs)->firstWhere(0, $tab)[1] ?? __('My Account');
@endphp
    @include('storefront.partials.page-band', ['title' => trim($__env->yieldContent('account_heading')) ?: $pageTitle, 'crumbs' => [[__('My Account'), store_route('account.dashboard')]], 'crumb' => $pageTitle])

    <div class="wrap" style="padding-top:clamp(24px,3vw,40px); padding-bottom:clamp(40px,5vw,64px);">
      <div class="acc-g">
        <aside style="display:flex; flex-direction:column; gap:14px;">
          <div style="display:flex; align-items:center; gap:14px; padding:18px; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:20px;">
            <span style="display:inline-flex; width:56px; height:56px; flex:0 0 auto; border-radius:50%; background:var(--fn-primary); color:#FFFFFF; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:19px; align-items:center; justify-content:center; box-shadow:0 0 0 4px var(--fn-primary-tint);">{{ $initials }}</span>
            <span style="min-width:0; line-height:1.35;">
              <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $customer->name }}</strong>
              <span style="display:block; font-size:13px; color:var(--fn-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $customer->email }}</span>
            </span>
          </div>
          <nav class="acc-nav" aria-label="{{ __('My account') }}" style="padding:8px; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:20px;">
            @foreach ($tabs as [$key, $label, $href, $icon, $badge])
              @php $on = $key === $tab; @endphp
              <a class="fh-74ffb8" href="{{ $href }}" @if ($on) aria-current="page" @endif style="flex:0 0 auto; display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; font-size:14.5px; font-weight:600; background:{{ $on ? 'var(--fn-primary)' : 'transparent' }}; color:{{ $on ? '#FFFFFF' : 'var(--fn-ink)' }}; white-space:nowrap;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"></path></svg>
                {{ $label }}
                @if ($badge)<span style="margin-left:auto; min-width:22px; height:20px; padding:0 7px; border-radius:999px; background:{{ $on ? '#FFFFFF' : 'var(--fn-primary-tint)' }}; color:{{ $on ? 'var(--fn-primary-dark)' : 'var(--fn-primary-dark)' }}; font-size:11px; font-weight:700; display:inline-flex; align-items:center; justify-content:center;">{{ $badge }}</span>@endif
              </a>
            @endforeach
            <form method="POST" action="{{ store_route('logout') }}" style="margin:0; display:contents;">@csrf
              <button class="fh-e15e24" type="submit" style="flex:0 0 auto; display:flex; align-items:center; gap:12px; padding:12px 14px; border:0; border-radius:12px; background:transparent; color:#D93A2B; font-size:14.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"></path><path d="M10 17l-5-5 5-5"></path><path d="M5 12h11"></path></svg>
                {{ __('Sign out') }}
              </button>
            </form>
          </nav>
        </aside>
        <section style="min-width:0; display:flex; flex-direction:column; gap:20px;">
          @yield('account')
        </section>
      </div>
    </div>
@endsection
