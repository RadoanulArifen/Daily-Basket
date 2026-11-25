@php
    // Error pages can render before any route middleware runs, so pick the language from the URL.
    $localization = app(\App\Support\Localization::class);
    $isAdmin = request()->is(config('store.admin_path'), config('store.admin_path').'/*');
    $locale = $isAdmin ? (auth('admin')->user()?->locale ?? session('admin_locale', $localization->default())) : $localization->fromRequest(request());
    app()->setLocale($localization->isSupported($locale) ? $locale : $localization->default());
    $home = $isAdmin ? url(config('store.admin_path')) : $localization->url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $localization->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @include('partials.favicons')
    <title>{{ $code }} · {{ __("store.errors.{$code}.title") }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:#fcfbf9;color:#22262a;font-family:'IBM Plex Sans','IBM Plex Sans Arabic',system-ui,-apple-system,'Segoe UI',Tahoma,sans-serif;line-height:1.5}
        .band{position:fixed;inset:0 0 auto 0;height:12px;background:linear-gradient(135deg,#9b2c24 25%,transparent 25%) -6px 0/12px 12px,linear-gradient(225deg,#9b2c24 25%,transparent 25%) -6px 0/12px 12px,linear-gradient(315deg,#22262a 25%,transparent 25%) 0 0/12px 12px,linear-gradient(45deg,#22262a 25%,transparent 25%) 0 0/12px 12px,#e8dfd0}
        main{max-width:28rem;text-align:center}
        .code{font-size:.875rem;font-weight:600;color:#9b2c24;letter-spacing:.02em}
        h1{margin:.5rem 0 0;font-size:1.75rem;line-height:1.2;font-weight:600}
        p{margin:1rem 0 0;color:#4a4f55}
        a{display:inline-block;margin-top:2rem;padding:.75rem 1.5rem;border-radius:.375rem;background:#22262a;color:#fcfbf9;text-decoration:none;font-weight:500}
        a:focus-visible{outline:2px solid #22262a;outline-offset:3px}
    </style>
</head>
<body>
    <div class="band" aria-hidden="true"></div>
    <main>
        <p class="code">{{ $code }}</p>
        <h1>{{ __("store.errors.{$code}.title") }}</h1>
        <p>{{ __("store.errors.{$code}.text") }}</p>
        <a href="{{ $home }}">{{ __('store.errors.home') }}</a>
    </main>
</body>
</html>
