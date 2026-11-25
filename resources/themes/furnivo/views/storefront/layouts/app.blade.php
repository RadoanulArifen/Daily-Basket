{{--
    Furnivo storefront layout — structure and styles from the design (site.zip).
    Pages with a full-bleed hero set @section('overHero', true): the header then starts
    transparent over it. Other pages get the design's 120px top spacing.
--}}
@php
    $storeName = settings()->trans('general.store_name') ?? config('app.name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.$storeName : ((settings()->trans('general.meta_title')) ?: $storeName);
    $description = trim($__env->yieldContent('meta_description')) ?: settings()->trans('general.meta_description');
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $overHero = View::hasSection('overHero');
    // The announcement bar makes the fixed header 40px taller.
    $headerOffset = settings()->bool('general.announcement_enabled') && filled(settings()->trans('general.announcement_text')) ? 160 : 120;
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
    <meta name="theme-color" content="#1D1D1D">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ $pageTitle ?: $fullTitle }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="en_US">
    @hasSection('og_image')@else<meta property="og:image" content="{{ asset('themes/furnivo/og-image.png') }}"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">@endif
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')

    @include('partials.favicons')
    @include('storefront.partials.analytics')

    {{-- Pages not yet converted to Furnivo (no @section('furnivo')) also get the base styles. --}}
    @unless (View::hasSection('furnivo'))
        @vite(['resources/css/storefront.css'])
    @endunless
    {{-- One <link> per language for this exact page, plus x-default. Search engines need
         these to show the right language and to avoid treating the two as duplicates. --}}
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

    @vite(['resources/themes/furnivo/css/theme.css', 'resources/themes/furnivo/js/theme.js'])
    @if ($css = $customCode->css())<style id="custom-css">{!! $css !!}</style>@endif
    {!! $customCode->html('head') !!}
</head>
<body>
    <a href="#content" class="fv-sr fv-sr-focus">{{ __('store.common.skip_to_content') }}</a>

    <div style="min-height: 100vh; display: flex; flex-direction: column;">
        @include('storefront.partials.header', ['storeName' => $storeName, 'overHero' => $overHero])

        <main id="content" class="fx-main fx-shell" style="flex: 1 1 0%;">
            @if ($overHero)
                @yield('content')
            @else
                <div style="padding-top: {{ $headerOffset }}px;">@yield('content')</div>
            @endif
        </main>

        @include('storefront.partials.footer', ['storeName' => $storeName])

        @include('storefront.partials.custom-order-modal')

        {{-- Slide-out bag (the design's cart drawer); contents from storefront/cart/drawer --}}
        <div x-data="cartDrawer(@js(store_route('cart.drawer')), @js(rtrim(store_route('cart'), '/').'/items'))" @open-cart-drawer.window="show()" @keydown.escape.window="close()"
             :style="{ pointerEvents: open ? 'auto' : 'none' }" style="position: fixed; inset: 0px; z-index: 90; pointer-events: none; overflow: hidden;">
            <div @click="close()" :style="{ opacity: open ? 1 : 0 }" style="position: absolute; inset: 0px; background: rgba(15, 15, 15, 0.45); opacity: 0; transition: opacity 350ms;"></div>
            <div x-ref="panel" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" tabindex="-1" :inert="! open" inert @keydown.tab="trap($event)" x-html="html"
                 :style="{ transform: open ? 'translateX(0px)' : 'translateX(100%)' }"
                 style="position: absolute; top: 0px; right: 0px; bottom: 0px; width: min(420px, 100%); background: rgb(255, 255, 255); display: flex; flex-direction: column; transform: translateX(100%); transition: transform 420ms cubic-bezier(0.22, 1, 0.36, 1); box-shadow: rgba(0, 0, 0, 0.5) -20px 0px 50px -30px; outline: none;"></div>
        </div>

        {{-- Toast ("Added to your bag") — the design's toast, driven by the store's toasts --}}
        <div x-data="storeToasts(@js(session('store_toast')))" aria-live="polite">
            <template x-for="toast in items.slice(-1)" :key="toast.id">
                <div style="position: fixed; left: 50%; bottom: 32px; z-index: 95; transform: translateX(-50%); transition: transform 400ms cubic-bezier(0.22, 1, 0.36, 1), opacity 400ms;" x-transition.opacity>
                    <div style="display: flex; align-items: center; gap: 12px; background: rgb(29, 29, 29); color: rgb(255, 255, 255); padding: 15px 22px; box-shadow: rgba(0, 0, 0, 0.6) 0px 20px 40px -20px;">
                        <span style="width: 22px; height: 22px; flex-shrink: 0; border-radius: 50%; color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 12px;"
                              :style="{ background: toast.type === 'error' ? 'rgb(199, 64, 52)' : 'rgb(76, 175, 125)' }" x-text="toast.type === 'error' ? '!' : '✓'">✓</span>
                        <span style="font-size: 14px; letter-spacing: 0.02em;"><span x-text="toast.message"></span></span>
                        <button type="button" @click="dismiss(toast.id)" aria-label="{{ __('store.common.close') }}" style="border: none; background: transparent; color: rgb(255, 255, 255); opacity: 0.6; cursor: pointer; font-size: 14px; padding: 0px 0px 0px 6px;">✕</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {!! $customCode->html('body') !!}
    @if ($theme->previewing())
        <div style="position: fixed; left: 0px; right: 0px; bottom: 0px; z-index: 96; display: flex; align-items: center; justify-content: center; gap: 12px; background: rgb(26, 26, 26); color: rgb(255, 255, 255); padding: 8px 16px; font-size: 13px;">
            <span>{{ __('store.theme_preview.banner') }}</span>
            <a href="{{ route('admin.online-store.preview.exit') }}" style="background: rgba(255, 255, 255, 0.15); padding: 4px 10px; color: rgb(255, 255, 255);">{{ __('store.theme_preview.exit') }}</a>
        </div>
    @endif
    @include('storefront.partials.tour')
    @include('storefront.partials.recaptcha')
</body>
</html>
