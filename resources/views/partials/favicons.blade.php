{{-- The site icon, used by the storefront, the admin and the sign-in pages alike.
     The active theme's icon wins; otherwise the files in public/ (replace them to use
     your own). --}}
@php
    $themeIcon = 'themes/'.config('store.theme').'/favicon.png';
    $themeIcon180 = 'themes/'.config('store.theme').'/favicon-180.png';
    $hasThemeIcon = file_exists(public_path($themeIcon));
    $iconVersion = config('store.theme') === 'freshnest' ? '?v=2' : '';
@endphp
@if ($hasThemeIcon)
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset($themeIcon).$iconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset(file_exists(public_path($themeIcon180)) ? $themeIcon180 : $themeIcon).$iconVersion }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@endif
<link rel="manifest" href="{{ asset(config('store.theme') === 'freshnest' ? 'themes/freshnest/site.webmanifest' : 'site.webmanifest') }}">
<meta name="theme-color" content="#22262a">
