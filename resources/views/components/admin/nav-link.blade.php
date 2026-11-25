@props(['href', 'icon', 'active' => false, 'parent' => false])

<a href="{{ $href }}" @if ($active && ! $parent) aria-current="page" @endif {{ $attributes }}
   @class([
       'flex h-8 items-center gap-2.5 rounded-lg px-2 text-[13px] transition',
       'bg-brand-50 font-semibold text-brand-600 shadow-[0_1px_0_rgba(0,0,0,.05)]' => $active && ! $parent,
       'font-semibold text-brand-600 hover:bg-sidebar-hover' => $active && $parent,
       'font-medium text-ink-soft hover:bg-sidebar-hover hover:text-ink-strong' => ! $active,
   ])>
    <x-icon :name="$icon" @class(['size-4 shrink-0', 'text-brand-600' => $active, 'text-ink-soft' => ! $active]) />
    <span class="flex-1 truncate">{{ $slot }}</span>
    {{ $trailing ?? '' }}
</a>
