{{-- View tabs above a list: <x-admin.tabs :tabs="[['label' => 'All', 'url' => ..., 'active' => true, 'count' => 3]]" /> --}}
@props(['tabs'])

<nav {{ $attributes->class('flex gap-0.5 overflow-x-auto [scrollbar-width:none]') }} aria-label="{{ __('admin.common.views') }}">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif
           @class([
               'inline-flex h-7 shrink-0 items-center gap-1.5 rounded-lg px-2.5 text-[13px] transition',
               'bg-[#e3e3e3] font-semibold text-ink-strong' => $tab['active'],
               'font-medium text-ink-soft hover:bg-black/5 hover:text-ink-strong' => ! $tab['active'],
           ])>
            {{ $tab['label'] }}
            @if (! empty($tab['count']))
                <span class="rounded-md bg-black/5 px-1.5 text-xs font-medium text-ink-soft">{{ $tab['count'] }}</span>
            @endif
        </a>
    @endforeach
    {{ $slot }}
</nav>
