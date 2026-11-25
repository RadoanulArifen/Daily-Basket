@props(['icon' => 'sparkles', 'title', 'text' => null])

<div {{ $attributes->class('flex flex-col items-center px-6 py-12 text-center') }}>
    <span class="flex size-20 items-center justify-center rounded-full bg-subdued text-[#8a8a8a] ring-8 ring-subdued/60">
        <x-icon :name="$icon" class="size-8" />
    </span>
    <h3 class="mt-5 text-base font-semibold text-ink-strong">{{ $title }}</h3>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
