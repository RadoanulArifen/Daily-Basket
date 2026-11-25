{{-- Language link + currency picker. Used in the top bar (desktop) and the mobile menu. --}}
@props(['currencies', 'current', 'tone' => 'dark'])

@php
    $localization = app(\App\Support\Localization::class);
    $other = collect($localization->codes())->first(fn ($code) => $code !== app()->getLocale());
@endphp

<div {{ $attributes->class('flex items-center gap-4') }}>
    @if ($other)
        <a href="{{ $localization->switchUrl($other) }}" hreflang="{{ $other }}" lang="{{ $other }}"
           class="inline-flex items-center gap-1.5 hover:underline underline-offset-4">
            <x-icon name="globe" class="size-4" />
            {{ $localization->nativeName($other) }}
        </a>
    @endif

    @if ($currencies->count() > 1)
        <form method="POST" action="{{ route('currency.switch') }}" class="relative">
            @csrf
            <label for="currency-{{ $tone }}" class="sr-only">{{ __('store.common.currency') }}</label>
            <select id="currency-{{ $tone }}" name="currency" x-data @change="$el.form.submit()"
                    @class([
                        'cursor-pointer appearance-none bg-transparent py-1 pe-5 ps-0 text-[13px] focus:outline-none',
                        'text-paper [&>option]:text-ink' => $tone === 'dark',
                        'text-ink' => $tone === 'light',
                    ])>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected($current->code === $currency->code)>{{ $currency->code }}</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" class="pointer-events-none absolute end-0 top-1/2 size-3.5 -translate-y-1/2" />
            <noscript><button type="submit" class="ms-2 underline">{{ __('store.common.apply') }}</button></noscript>
        </form>
    @endif
</div>
