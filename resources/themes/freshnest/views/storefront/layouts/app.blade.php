{{--
    FreshNest storefront layout — page frame from the design (freshnest-html): top bar and
    header, the page, the footer, the cart drawer and the toast.

    Pages built in the FreshNest design set @section('freshnest', true); pages not yet
    converted also get the base storefront stylesheet so they still look finished.
--}}
@php
    $storeName = settings()->trans('general.store_name') ?? config('app.name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.$storeName : ((settings()->trans('general.meta_title')) ?: $storeName);
    $description = trim($__env->yieldContent('meta_description')) ?: settings()->trans('general.meta_description');
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $theme = app(\App\Services\Theme\ThemeSettings::class);
    $customCode = app(\App\Services\Theme\CustomCode::class);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app(\App\Support\Localization::class)->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    @hasSection('noindex')<meta name="robots" content="noindex, follow">@else<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">@endif
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="theme-color" content="#23633F">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ $pageTitle ?: $fullTitle }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AE' : 'en_US' }}">
    @hasSection('og_image')@else<meta property="og:image" content="{{ asset('themes/freshnest/og-image.png') }}"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">@endif
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')

    @include('partials.favicons')
    @include('storefront.partials.analytics')

    @unless (View::hasSection('freshnest'))
        @vite(['resources/css/storefront.css'])
    @endunless
    @php
        $l10n = app(\App\Support\Localization::class);
        $bare = '/'.ltrim(preg_replace('#^/'.preg_quote($l10n->current(), '#').'(/|$)#', '/', '/'.ltrim(request()->path(), '/')), '/');
    @endphp
    @if (count($l10n->locales()) > 1)
        @foreach ($l10n->locales() as $code => $meta)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ $l10n->url($bare, $code) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $l10n->url($bare, $l10n->default()) }}">
    @endif

    @vite(['resources/themes/freshnest/css/theme.css', 'resources/themes/freshnest/js/theme.js'])
    @if ($css = $customCode->css())<style id="custom-css">{!! $css !!}</style>@endif
    {!! $customCode->html('head') !!}
</head>
<body class="fn-body">
    <a href="#content" class="fn-sr fn-sr-focus">{{ __('store.common.skip_to_content') }}</a>

    <div class="fn-page" style="width:100%; overflow-x:hidden; padding-bottom:0;">
        @include('storefront.partials.header', ['storeName' => $storeName])

        <main id="content">
            @yield('content')
        </main>

        @include('storefront.partials.footer', ['storeName' => $storeName])
    </div>

    {{-- Cart drawer (the design's slide-out cart); contents from storefront/cart/drawer --}}
    <div x-data="cartDrawer(@js(store_route('cart.drawer')), @js(rtrim(store_route('cart'), '/').'/items'))" @open-cart-drawer.window="show()" @keydown.escape.window="close()"
         :style="{ pointerEvents: open ? 'auto' : 'none' }" style="position:fixed; inset:0; z-index:100; pointer-events:none;">
        <div @click="close()" :style="{ opacity: open ? 1 : 0 }" style="position:absolute; inset:0; background:rgba(23,58,40,.45); backdrop-filter:blur(2px); opacity:0; transition:opacity 300ms;"></div>
        <aside x-ref="panel" class="fn-drawer" :class="open ? 'is-open' : ''" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" tabindex="-1" :inert="! open" inert @keydown.tab="trap($event)" x-html="html"
               :style="{ transform: open ? 'translateX(0px)' : 'translateX(100%)', boxShadow: open ? '-20px 0 50px rgba(0,0,0,.18)' : 'none' }"
               style="position:absolute; top:0; right:0; bottom:0; z-index:110; width:min(440px,100vw); background:var(--fn-canvas); display:flex; flex-direction:column; font-family:Inter, system-ui, sans-serif; color:var(--fn-ink); transform:translateX(100%); transition:transform 380ms cubic-bezier(0.22, 1, 0.36, 1); outline:none;"></aside>
    </div>

    {{-- Toast ("… added to cart") — the design's toast, driven by the store's toasts --}}
    <div x-data="storeToasts(@js(session('store_toast')))" aria-live="polite">
        <template x-for="toast in items.slice(-1)" :key="toast.id">
            <div style="position:fixed; left:50%; bottom:clamp(90px,10vw,32px); transform:translateX(-50%); z-index:120; display:flex; align-items:center; gap:10px; color:#FFFFFF; font-size:14px; font-weight:600; padding:13px 18px; border-radius:14px; box-shadow:0 16px 36px rgba(0,0,0,.22); max-width:calc(100vw - 32px);"
                 :style="{ background: toast.type === 'error' ? '#8F2A1F' : 'var(--fn-primary-dark)' }" x-transition.opacity>
                <span style="display:inline-flex; width:22px; height:22px; flex:0 0 auto; border-radius:50%; align-items:center; justify-content:center; font-size:12px;"
                      :style="{ background: toast.type === 'error' ? '#D4380D' : 'var(--fn-primary)' }" x-text="toast.type === 'error' ? '!' : '✓'">✓</span>
                <span x-text="toast.message"></span>
                <template x-if="toast.type !== 'error'">
                    <button type="button" @click="dismiss(toast.id); $dispatch('open-cart-drawer')" style="border:0; background:transparent; color:var(--fn-accent-light); font-weight:700; font-size:13.5px; cursor:pointer; padding:0 0 0 6px;">{{ __('View cart') }}</button>
                </template>
            </div>
        </template>
    </div>

    {!! $customCode->html('body') !!}
    @if ($theme->previewing())
        <div style="position:fixed; left:0; right:0; bottom:0; z-index:130; display:flex; align-items:center; justify-content:center; gap:12px; background:var(--fn-footer); color:#FFFFFF; padding:8px 16px; font-size:13px;">
            <span>{{ __('store.theme_preview.banner') }}</span>
            <a href="{{ route('admin.online-store.preview.exit') }}" style="background:rgba(255,255,255,.15); padding:4px 10px; color:#FFFFFF;">{{ __('store.theme_preview.exit') }}</a>
        </div>
    @endif
    @include('storefront.partials.tour')
    @include('storefront.partials.recaptcha')
</body>
</html>
