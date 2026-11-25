{{-- Page title row. Optional: back link, badges (inline after the title), actions (right). --}}
@props(['title', 'subtitle' => null, 'back' => null])

<div {{ $attributes->class('mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-3') }}>
    <div class="flex min-w-0 items-center gap-1.5">
        @if ($back)
            <a href="{{ $back }}" class="inline-flex size-7 shrink-0 items-center justify-center rounded-lg text-ink-soft hover:bg-black/5 hover:text-ink-strong" aria-label="{{ __('admin.common.back') }}">
                <x-icon name="arrow-left" class="size-4 rtl:rotate-180" />
            </a>
        @endif
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="truncate text-xl font-bold tracking-tight text-ink-strong">{{ $title }}</h1>
                {{ $badges ?? '' }}
            </div>
            @if ($subtitle)
                <p class="mt-0.5 text-[13px] text-muted">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
