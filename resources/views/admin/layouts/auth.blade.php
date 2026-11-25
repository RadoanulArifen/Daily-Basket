@php
    $localization = app(\App\Support\Localization::class);
    $storeName = settings()->trans('general.store_name') ?? config('app.name');
    // The logo from Settings → Store details, if one has been uploaded.
    $logoFile = settings()->get('general.logo');
    $logo = $logoFile ? \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($logoFile) : null;
    $otherLocale = collect($localization->codes())->first(fn ($code) => $code !== app()->getLocale());
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $localization->direction() }}" class="admin-auth h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @include('partials.favicons')
    <title>@yield('title') · {{ $storeName }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-full flex-col font-sans text-ink antialiased">
    <div class="flex justify-end p-4">
        @if ($otherLocale)
            <form method="POST" action="{{ route('admin.locale') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $otherLocale }}">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm text-ink-soft hover:bg-ink/5" lang="{{ $otherLocale }}">
                    <x-icon name="languages" class="size-4" /> {{ $localization->nativeName($otherLocale) }}
                </button>
            </form>
        @endif
    </div>

    <main class="flex flex-1 items-start justify-center px-4 pb-16 sm:items-center">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                @if ($logo)
                    {{-- Inline sizes: these do not depend on the compiled stylesheet, so the logo is the
                         right size even before an asset build. --}}
                    <img src="{{ $logo }}" alt="{{ $storeName }}"
                         style="height: 40px; width: auto; max-width: 220px; object-fit: contain; object-position: center; display: block; margin-inline: auto;">
                @else
                    <span class="flex size-11 items-center justify-center rounded-xl bg-brand-600 text-lg font-semibold text-white">{{ mb_substr($storeName, 0, 1) }}</span>
                    <p class="mt-3 text-sm text-muted">{{ $storeName }}</p>
                @endif
                <h1 class="mt-1 text-xl font-semibold tracking-tight">@yield('heading')</h1>
            </div>

            <div class="rounded-2xl border border-line bg-surface p-6 shadow-card">
                @if (session('status'))
                    <x-admin.alert tone="success" class="mb-5">{{ session('status') }}</x-admin.alert>
                @endif

                @yield('content')
            </div>

            @hasSection('footer')
                <p class="mt-6 text-center text-sm text-muted">@yield('footer')</p>
            @endif
        </div>
    </main>
</body>
</html>
