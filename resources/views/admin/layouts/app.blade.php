@php
    $localization = app(\App\Support\Localization::class);
    $admin = auth('admin')->user();
    $storeName = settings()->trans('general.store_name') ?? config('app.name');
    $tourOverview = app(\App\Services\Tours\AdminTours::class)->overview($admin);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $localization->direction() }}" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <title>@hasSection('title')@yield('title') · @endif{{ $storeName }} {{ __('admin.common.admin') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="h-full font-sans text-ink antialiased">
    <a href="#main" class="sr-only start-0 top-0 focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[80] focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:shadow-lg">{{ __('admin.common.skip_to_content') }}</a>

    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-full">
        @include('admin.partials.topbar', ['admin' => $admin, 'storeName' => $storeName])

        {{-- Mobile sidebar backdrop --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 top-14 z-30 bg-black/40 lg:hidden"></div>

        @include('admin.partials.sidebar', ['storeName' => $storeName])

        <div class="pt-14 lg:ps-60">
            @if (config('store.demo'))
                {{-- A public demo: say so before anyone wonders why their change vanished. --}}
                <div class="flex items-center gap-2 border-b border-attention-200 bg-attention-50 px-4 py-2.5 text-[13px] text-attention-800">
                    <x-icon name="info" class="size-4 shrink-0" />
                    <span>{{ __('admin.demo.banner') }}</span>
                </div>
            @endif

            <main id="main" @class([
                'mx-auto w-full px-3 py-5 sm:px-6 lg:py-6 xl:px-8',
                // Lists (products, orders, customers …) use the whole screen on large displays, like
                // Shopify; detail and settings pages stay a comfortable reading width, a bit wider on 2xl.
                'max-w-none' => View::hasSection('fullWidth'),
                'max-w-[62rem] 2xl:max-w-[72rem]' => ! View::hasSection('fullWidth'),
            ])>
                @yield('content')
            </main>
        </div>
    </div>

    @include('admin.partials.search')
    @include('admin.partials.tours')
    <x-admin.toasts />
</body>
</html>
