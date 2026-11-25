@props(['title' => null, 'description' => null, 'padding' => true, 'subdued' => false])

<section {{ $attributes->class(['overflow-hidden rounded-xl shadow-card', 'bg-subdued' => $subdued, 'bg-surface' => ! $subdued]) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 px-4 pt-4">
            <div class="min-w-0">
                @if ($title)<h2 class="text-sm font-semibold text-ink-strong">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-[13px] text-muted">{{ $description }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-1.5">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-4' => $padding, 'pt-3' => $padding && $title])>
        {{ $slot }}
    </div>
</section>
