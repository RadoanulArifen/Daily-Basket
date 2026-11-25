{{-- Settings-style row: explanation on the left, fields card on the right (stacked on mobile). --}}
@props(['title', 'description' => null])

<div {{ $attributes->class('grid gap-3 border-b border-line pb-6 last-of-type:border-0 lg:grid-cols-[18rem_1fr] lg:gap-8') }}>
    <div class="lg:pt-3">
        <h2 class="text-sm font-semibold text-ink-strong">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-xs text-[13px] leading-5 text-muted">{{ $description }}</p>
        @endif
    </div>
    <div class="space-y-4 rounded-xl bg-surface p-4 shadow-card">
        {{ $slot }}
    </div>
</div>
