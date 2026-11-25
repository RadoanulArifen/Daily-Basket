@php
    $me = auth('admin')->user();
    // Quick links shown before typing, filtered by the admin's role.
    $shortcuts = collect([
        ['label' => __('admin.search.go.orders'), 'route' => 'admin.orders.index', 'icon' => 'shopping-bag'],
        ['label' => __('admin.search.go.add_product'), 'route' => 'admin.products.create', 'icon' => 'plus'],
        ['label' => __('admin.search.go.products'), 'route' => 'admin.products.index', 'icon' => 'package'],
        ['label' => __('admin.search.go.customers'), 'route' => 'admin.customers.index', 'icon' => 'users'],
        ['label' => __('admin.search.go.discount'), 'route' => 'admin.discounts.create', 'icon' => 'badge-percent'],
        ['label' => __('admin.search.go.returns'), 'route' => 'admin.returns.index', 'icon' => 'undo-2'],
        ['label' => __('admin.search.go.analytics'), 'route' => 'admin.analytics', 'icon' => 'chart-column'],
        ['label' => __('admin.search.go.settings'), 'route' => 'admin.settings.index', 'icon' => 'settings'],
    ])->filter(fn ($s) => ! ($area = \App\Enums\AdminRole::areaForRoute($s['route'])) || $me?->mayAccess($area))
      ->map(fn ($s) => ['title' => $s['label'], 'url' => route($s['route']), 'icon' => $s['icon']])->values();
@endphp

<div x-data="adminSearch({ url: @js(route('admin.search')), shortcuts: @js($shortcuts) })"
     @open-search.window="show()"
     @keydown.window.prevent.cmd.k="show()" @keydown.window.prevent.ctrl.k="show()"
     x-show="open" x-cloak class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="{{ __('admin.search.title') }}">
    <div class="absolute inset-0 bg-black/40" @click="close()"></div>

    <div class="relative mx-auto mt-2 w-[calc(100%-1rem)] max-w-[40rem] overflow-hidden rounded-xl bg-surface shadow-popover sm:mt-16" @keydown.escape.prevent="close()">
        <div class="flex items-center gap-2 border-b border-line px-3">
            <x-icon name="search" class="size-4 text-muted" />
            <input x-ref="input" x-model="q" @input.debounce.200ms="search()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="go()"
                   type="search" autocomplete="off" spellcheck="false" placeholder="{{ __('admin.search.placeholder') }}"
                   class="h-12 flex-1 border-0 bg-transparent text-sm outline-none focus:outline-none" role="combobox" aria-controls="search-results" :aria-expanded="items.length > 0" :aria-activedescendant="items.length ? 'search-item-' + active : null">
            <span x-show="loading" class="size-4 animate-spin rounded-full border-2 border-line border-t-ink"></span>
            <kbd class="rounded border border-line px-1.5 text-[11px] text-muted">Esc</kbd>
        </div>

        <div id="search-results" role="listbox" class="max-h-[60vh] overflow-y-auto py-1.5">
            <template x-for="group in view" :key="group.label">
                <div class="px-1.5 pb-1">
                    <p class="px-2 pt-2 pb-1 text-xs font-medium text-muted" x-text="group.label"></p>
                    <template x-for="item in group.items" :key="item.url">
                        <a :href="item.url" :id="'search-item-' + item.index" role="option" :aria-selected="active === item.index"
                           @mouseenter="active = item.index"
                           class="flex items-center gap-3 rounded-lg px-2 py-1.5" :class="active === item.index ? 'bg-subdued' : ''">
                            <template x-if="item.image"><img :src="item.image" alt="" class="size-8 rounded-md border border-line object-cover"></template>
                            <template x-if="! item.image"><span class="flex size-8 items-center justify-center rounded-md bg-subdued text-muted" x-html="icons[item.icon] ?? ''"></span></template>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink-strong" x-text="item.title"></span>
                                <span class="block truncate text-xs text-muted" x-show="item.meta" x-text="item.meta"></span>
                            </span>
                            <x-icon name="corner-down-left" class="size-3.5 text-muted" x-show="active === item.index" />
                        </a>
                    </template>
                </div>
            </template>
            <p x-show="q.length >= 2 && ! loading && ! view.length" class="px-4 py-8 text-center text-sm text-muted">{{ __('admin.search.nothing') }}</p>
        </div>

        <div class="flex items-center gap-4 border-t border-line bg-subdued px-3 py-2 text-xs text-muted">
            <span class="flex items-center gap-1"><kbd class="rounded border border-line bg-surface px-1">↑</kbd><kbd class="rounded border border-line bg-surface px-1">↓</kbd> {{ __('admin.search.move') }}</span>
            <span class="flex items-center gap-1"><kbd class="rounded border border-line bg-surface px-1">↵</kbd> {{ __('admin.search.open') }}</span>
        </div>
    </div>

    {{-- Icon markup for the result list (rendered once, reused by the script). --}}
    <template x-ref="icons">
        @foreach (['shopping-bag', 'package', 'users', 'plus', 'badge-percent', 'undo-2', 'chart-column', 'settings'] as $name)
            <span data-icon="{{ $name }}"><x-icon :name="$name" class="size-4" /></span>
        @endforeach
    </template>
</div>
