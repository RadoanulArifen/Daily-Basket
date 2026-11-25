{{-- Shared frame for login / register / password pages. --}}
@props(['heading', 'intro' => null])

<section class="mx-auto w-full max-w-md px-4 py-12 sm:py-20">
    <h1 class="text-3xl font-semibold tracking-tight">{{ $heading }}</h1>
    @if ($intro)
        <p class="mt-3 text-[15px] leading-relaxed text-ink-soft">{{ $intro }}</p>
    @endif

    @if (session('status'))
        <x-store.alert class="mt-6">{{ session('status') }}</x-store.alert>
    @endif

    <div class="mt-8">
        {{ $slot }}
    </div>
</section>
