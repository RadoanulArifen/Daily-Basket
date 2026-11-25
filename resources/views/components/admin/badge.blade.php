{{-- Status badge. tone: neutral | success | warning | attention | danger | info | brand. dot: filled / half / empty circle before the text. --}}
@props(['tone' => 'neutral', 'dot' => null])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-xs font-medium whitespace-nowrap',
    match ($tone) {
        'success' => 'bg-success-100 text-success-700',
        'warning' => 'bg-warning-100 text-warning-700',
        'attention' => 'bg-attention-100 text-attention-700',
        'danger' => 'bg-danger-100 text-danger-700',
        'info', 'brand' => 'bg-info-100 text-info-700',
        default => 'bg-[#e3e3e3] text-ink',
    },
]) }}>
    @if ($dot === 'full')<span class="size-2 rounded-full bg-current opacity-80"></span>
    @elseif ($dot === 'half')<span class="size-2 rounded-full border-[1.5px] border-current [background:linear-gradient(90deg,currentColor_50%,transparent_50%)] opacity-80"></span>
    @elseif ($dot === 'empty')<span class="size-2 rounded-full border-[1.5px] border-current opacity-80"></span>@endif
    {{ $slot }}
</span>
