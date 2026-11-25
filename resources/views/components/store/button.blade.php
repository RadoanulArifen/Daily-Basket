@props([
    'variant' => 'primary', // primary | secondary | link
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 font-medium transition disabled:pointer-events-none disabled:opacity-50',
        match ($variant) {
            'secondary' => 'h-12 rounded-md border border-ink px-6 text-[15px] text-ink hover:bg-ink hover:text-paper',
            'link' => 'text-[15px] text-ink underline decoration-ink/30 underline-offset-4 hover:decoration-ink',
            default => 'h-12 rounded-md bg-ink px-6 text-[15px] text-paper hover:bg-ink-soft',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
