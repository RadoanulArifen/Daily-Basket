@php
    $localization = app(\App\Support\Localization::class);
    $storeName = settings()->trans('general.store_name') ?? config('app.name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.$storeName : $storeName.(($tagline = settings()->trans('general.tagline')) ? ' | '.$tagline : '');
    $description = trim($__env->yieldContent('meta_description')) ?: settings()->trans('general.meta_description');
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $localization->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $fullTitle }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    @hasSection('noindex')<meta name="robots" content="noindex, follow">@endif
    <link rel="canonical" href="{{ $canonical }}">
    @foreach ($localization->codes() as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $localization->switchUrl($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $localization->switchUrl($localization->default()) }}">

    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ $pageTitle ?: $storeName }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_AE' : 'en_AE' }}">
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')

    @include('partials.favicons')
    @include('storefront.partials.analytics')

    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])

    {{-- Online Store → Customize (colours, font) and Custom code (CSS, head code) --}}
    @php $theme = app(\App\Services\Theme\ThemeSettings::class); $customCode = app(\App\Services\Theme\CustomCode::class); @endphp
    @if ($themeCss = $theme->css())<style id="theme-settings">{!! $themeCss !!}</style>@endif
    @if ($css = $customCode->css())<style id="custom-css">{!! $css !!}</style>@endif
    {!! $customCode->html('head') !!}
</head>
<body class="flex min-h-screen flex-col bg-paper font-sans text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[70] focus:rounded-md focus:bg-paper focus:px-4 focus:py-2 focus:shadow-lg">{{ __('store.common.skip_to_content') }}</a>

    @include('storefront.partials.header', ['storeName' => $storeName])

    <main id="content" class="flex-1">
        @yield('content')
    </main>

    @include('storefront.partials.footer', ['storeName' => $storeName])

    {{-- Short confirmations ("Added to your bag") --}}
    <div x-data="storeToasts(@js(session('store_toast')))" class="pointer-events-none fixed inset-x-4 bottom-4 z-[60] flex flex-col items-center gap-2 sm:inset-x-auto sm:end-6 sm:bottom-6 sm:items-end" aria-live="polite">
        <template x-for="toast in items" :key="toast.id">
            <div x-transition.opacity class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-md px-4 py-3 text-[15px] shadow-lg"
                 :class="toast.type === 'error' ? 'bg-danger text-white' : 'bg-ink text-paper'">
                <span class="flex-1" x-text="toast.message"></span>
                <a x-show="toast.type !== 'error'" href="{{ store_route('cart') }}" class="shrink-0 text-sm underline underline-offset-4">{{ __('store.cart.view_bag') }}</a>
                <button type="button" @click="dismiss(toast.id)" class="shrink-0 opacity-70 hover:opacity-100" aria-label="{{ __('store.common.close') }}"><x-icon name="x" class="size-4" /></button>
            </div>
        </template>
    </div>

    {!! $customCode->html('body') !!}

    @if ($theme->previewing())
        <div class="fixed inset-x-0 bottom-0 z-[90] flex items-center justify-center gap-3 bg-[#1a1a1a] px-4 py-2 text-[13px] text-white">
            <span>{{ __('store.theme_preview.banner') }}</span>
            <a href="{{ route('admin.online-store.preview.exit') }}" class="rounded-md bg-white/15 px-2.5 py-1 font-medium hover:bg-white/25">{{ __('store.theme_preview.exit') }}</a>
        </div>
    @endif
    @include('storefront.partials.tour')
    @include('storefront.partials.recaptcha')
</body>
</html>
