@props(['tone' => 'info', 'title' => null]) {{-- info | success | warning | danger --}}

@php
    $icon = ['success' => 'circle-check', 'warning' => 'triangle-alert', 'danger' => 'circle-x'][$tone] ?? 'info';
@endphp

<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class([
    'flex gap-3 rounded-xl p-3 text-[13px] shadow-card',
    match ($tone) {
        'success' => 'bg-success-50 text-success-700 [&>svg]:text-success-600',
        'warning' => 'bg-warning-50 text-warning-700 [&>svg]:text-warning-600',
        'danger' => 'bg-danger-50 text-danger-700 [&>svg]:text-danger-600',
        default => 'bg-info-50 text-info-700 [&>svg]:text-info-600',
    },
]) }}>
    <x-icon :name="$icon" class="mt-px size-5" />
    <div class="min-w-0 text-ink-soft">
        @if ($title)<p class="font-medium text-ink">{{ $title }}</p>@endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
</div>
