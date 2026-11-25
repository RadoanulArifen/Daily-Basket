@props([
    'variant' => 'primary', // primary | secondary | danger | ghost | plain
    'size' => 'md',         // sm | md
    'href' => null,
    'icon' => null,
    'type' => 'submit',
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold whitespace-nowrap transition select-none active:translate-y-px',
        'disabled:pointer-events-none disabled:opacity-40',
        match ($size) {
            'sm' => 'h-7 px-2.5 text-xs',
            default => 'h-8 px-3 text-[13px]',
        },
        match ($variant) {
            'secondary' => 'bg-surface text-ink-strong shadow-button ring-1 ring-black/10 hover:bg-subdued',
            'danger' => 'bg-danger-600 text-white shadow-button-primary hover:bg-danger-700',
            'ghost' => 'text-ink-soft hover:bg-black/5 hover:text-ink-strong',
            'plain' => 'h-auto! px-0! text-brand-600 hover:underline',
            default => 'bg-brand-600 text-white shadow-button-primary hover:bg-brand-700',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
