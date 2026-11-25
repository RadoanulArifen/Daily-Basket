@php
    $logo = setting('general.logo');
    $name = settings()->trans('general.store_name') ?? config('app.name');
@endphp

<a href="{{ store_route('home') }}" {{ $attributes->class('inline-flex items-center') }} aria-label="{{ $name }} — {{ __('store.nav.home') }}">
    @if ($logo)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($logo) }}" alt="{{ $name }}" class="h-8 w-auto max-w-40 object-contain sm:h-9">
    @else
        {{-- Demo logo until a real one is uploaded --}}
        <span class="inline-flex items-center gap-2.5">
            <x-store.brand-mark class="size-8 shrink-0 sm:size-9" />
            <span class="flex flex-col leading-none">
                <span class="text-lg font-semibold tracking-tight text-ink sm:text-xl">{{ $name }}</span>
                <span class="mt-1 hidden text-[10px] font-medium tracking-[0.2em] text-stone uppercase sm:block rtl:tracking-normal">{{ __('store.brand.tagline') }}</span>
            </span>
        </span>
    @endif
</a>
