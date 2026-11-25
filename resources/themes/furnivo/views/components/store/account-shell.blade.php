@props(['ordersCount' => null])

{{-- Account shell — the design's band, sidebar and panel. Each account page fills $slot. --}}
@php
    $customer = auth('web')->user();
    $tabs = [
        ['label' => __('store.account.orders'), 'url' => store_route('account.orders'), 'route' => 'account.orders', 'count' => $ordersCount ?? null],
        ['label' => __('store.account.details'), 'url' => store_route('account.dashboard'), 'route' => 'account.dashboard'],
        ['label' => __('store.addresses.title'), 'url' => store_route('account.addresses'), 'route' => 'account.addresses'],
        ['label' => __('store.account.saved_pieces'), 'url' => store_route('wishlist'), 'route' => 'wishlist'],
    ];
@endphp

<section style="width: 100%; padding: 0px 0px clamp(60px, 7vw, 100px);">
    <div style="width: 100%; background: rgb(245, 244, 240); padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(36px, 4.5vw, 56px); margin-bottom: clamp(36px, 4.5vw, 52px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ __('store.account.title') }}</span>
        </nav>
        <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 12px;">{{ __('store.account.title') }}</div>
        <h1 style="margin: 0px; font-size: clamp(30px, 3.6vw, 44px); font-weight: 800; letter-spacing: -0.03em;">{{ __('store.account.greeting', ['name' => \Illuminate\Support\Str::before($customer->name, ' ')]) }}</h1>
    </div>

    <div style="padding: 0px clamp(24px, 4.5vw, 80px);">
        <div class="acct-cols fx-account-layout" style="display: grid; grid-template-columns: 240px 1fr; gap: clamp(28px, 4vw, 56px); align-items: start;">
            <aside class="acct-tabs fx-account-nav no-sb" style="display: flex; flex-direction: column; gap: 2px; overflow-x: auto; min-width: 0px;" aria-label="{{ __('store.account.title') }}">
                @foreach ($tabs as $tab)
                    @php $on = request()->routeIs('*'.$tab['route']); @endphp
                    <a href="{{ $tab['url'] }}" @if ($on) aria-current="page" @endif
                       style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 16px; font-size: 15px; transition: background 200ms, box-shadow 200ms; {{ $on ? 'color: rgb(32, 32, 32); background: rgb(250, 249, 247); box-shadow: rgb(228, 225, 218) 0px 0px 0px 1px inset;' : 'color: rgb(102, 102, 102); background: transparent; box-shadow: transparent 0px 0px 0px 1px inset;' }}">
                        <span><span>{{ $tab['label'] }}</span></span>
                        @if (($tab['count'] ?? null) !== null)<span style="font-size: 12px; color: rgb(139, 135, 127);">{{ $tab['count'] }}</span>@endif
                    </a>
                @endforeach
                <form method="POST" action="{{ store_route('logout') }}" style="margin-top: 10px;">
                    @csrf
                    <button style="width: 100%; text-align: start; padding: 13px 16px; border: none; background: transparent; color: rgb(102, 102, 102); font-size: 15px; cursor: pointer;">{{ __('store.account.sign_out') }}</button>
                </form>
            </aside>

            <div style="min-width: 0px;">
                @if (session('status'))
                    <p role="status" style="margin: 0px 0px 20px; padding: 14px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 14px;">{{ session('status') }}</p>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
