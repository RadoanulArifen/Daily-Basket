@php $storeName = settings()->trans('general.store_name') ?? config('app.name'); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app(\App\Support\Localization::class)->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @include('partials.favicons')
    <title>{{ $storeName }}</title>
    @vite(['resources/css/storefront.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-wool-soft px-4 font-sans text-ink antialiased">
    <main class="w-full max-w-sm text-center">
        <x-store.brand-mark class="mx-auto size-14" />
        <h1 class="mt-5 text-2xl font-semibold tracking-tight">{{ $storeName }}</h1>
        <p class="mt-3 text-[15px] leading-relaxed text-ink-soft">{{ $message ?: __('store.password_page.default') }}</p>
        <form method="POST" action="{{ route('store.password.unlock') }}" class="mt-8 space-y-3 text-start">
            @csrf
            <label for="password" class="block text-sm font-medium">{{ __('store.password_page.label') }}</label>
            <input id="password" type="password" name="password" required autofocus class="store-field">
            @error('password')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            <button class="h-11 w-full rounded-md bg-ink text-[15px] font-medium text-paper hover:bg-brand-red">{{ __('store.password_page.enter') }}</button>
        </form>
        <p class="mt-8 text-xs text-stone">{{ __('store.password_page.owner') }} <a href="{{ route('admin.login') }}" class="underline">{{ __('store.password_page.owner_link') }}</a></p>
    </main>
</body>
</html>
