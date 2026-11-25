{{--
    Top of a list card: view tabs (built-in + saved), search, filter pills and sort.

    <x-admin.index-filters resource="orders" route="admin.orders.index" :tabs="[['key' => 'all', 'label' => 'All', 'count' => null], …]"
        :sorts="['newest' => 'Newest', …]" :placeholder="…">
        <x-slot:filters> <x-admin.filter-pill name="payment" :options="[…]" :label="…" /> … </x-slot:filters>
    </x-admin.index-filters>
--}}
@props(['resource', 'route', 'tabs' => [], 'sorts' => [], 'placeholder' => null, 'tabParam' => 'tab'])

@php
    $request = request();
    $views = \App\Models\AdminView::query()->where('resource', $resource)->orderBy('id')->get();
    $current = \App\Models\AdminView::normalize($request->query());
    $activeView = $views->firstWhere('id', (int) $request->query('view'));
    $currentTab = $request->query($tabParam, $tabs[0]['key'] ?? null);
    $hasFilters = collect($current)->except(['view', $tabParam, 'sort'])->isNotEmpty();
    $sort = $request->query('sort', array_key_first($sorts));
    $keep = fn (array $extra) => array_filter(array_merge(collect($current)->except(['view'])->all(), $extra), fn ($v) => $v !== null);
@endphp

<div class="border-b border-line">
    {{-- Views --}}
    <div class="flex items-center gap-2 px-2 pt-2">
        <x-admin.tabs class="flex-1" :tabs="array_merge(
            array_map(fn ($t) => ['label' => $t['label'], 'count' => $t['count'] ?? null, 'url' => route($route, $t['key'] === ($tabs[0]['key'] ?? null) ? [] : [$tabParam => $t['key']]), 'active' => ! $activeView && ! $hasFilters && $currentTab === $t['key']], $tabs),
            $views->map(fn ($v) => ['label' => $v->name, 'url' => route($route, $v->query + ['view' => $v->id]), 'active' => $activeView?->id === $v->id])->all(),
        )">
            @if ($activeView)
                <form method="POST" action="{{ route('admin.views.destroy', $activeView) }}" x-data @submit="if (! confirm(@js(__('admin.views.delete_confirm', ['name' => $activeView->name])))) $event.preventDefault()">
                    @csrf @method('DELETE')
                    <button class="inline-flex size-7 items-center justify-center rounded-lg text-muted hover:bg-black/5 hover:text-danger-600" title="{{ __('admin.views.delete') }}" aria-label="{{ __('admin.views.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button>
                </form>
            @endif
        </x-admin.tabs>

        @if ($hasFilters && ! $activeView)
            <form method="POST" action="{{ route('admin.views.store') }}" x-data
                  @submit="const n = prompt(@js(__('admin.views.name_prompt'))); if (! n) { $event.preventDefault(); return; } $el.querySelector('[name=name]').value = n">
                @csrf
                <input type="hidden" name="resource" value="{{ $resource }}">
                <input type="hidden" name="name" value="">
                <input type="hidden" name="query" value="{{ http_build_query(collect($current)->except(['view'])->all()) }}">
                <x-admin.button variant="ghost" size="sm" icon="bookmark-plus">{{ __('admin.views.save') }}</x-admin.button>
            </form>
        @endif
    </div>

    {{-- Search, filters, sort --}}
    <form method="GET" action="{{ route($route) }}" class="flex flex-wrap items-center gap-2 p-2">
        @foreach (collect($current)->only([$tabParam, 'sort'])->all() as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <label class="relative min-w-48 flex-1">
            <span class="sr-only">{{ $placeholder ?? __('admin.common.search') }}</span>
            <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input type="search" name="q" value="{{ $request->query('q') }}" placeholder="{{ $placeholder ?? __('admin.common.search') }}" class="admin-field ps-8">
        </label>

        {{ $filters ?? '' }}

        @if ($hasFilters)
            <a href="{{ route($route, collect($current)->only([$tabParam])->all()) }}" class="text-[13px] font-medium text-brand-600 hover:underline">{{ __('admin.views.clear') }}</a>
        @endif

        @if ($sorts)
            <div x-data="{ open: false }" class="relative ms-auto" @keydown.escape="open = false">
                <button type="button" @click="open = ! open" :aria-expanded="open" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-surface px-2.5 text-[13px] font-medium shadow-button ring-1 ring-black/10 hover:bg-subdued" title="{{ __('admin.views.sort') }}">
                    <x-icon name="arrow-up-down" class="size-4" /> <span class="hidden sm:inline">{{ $sorts[$sort] ?? '' }}</span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" class="absolute end-0 z-20 mt-1 w-52 rounded-xl bg-surface p-1.5 shadow-popover" role="menu">
                    <p class="px-2 py-1 text-xs font-medium text-muted">{{ __('admin.views.sort_by') }}</p>
                    @foreach ($sorts as $key => $label)
                        <a href="{{ route($route, $keep(['sort' => $key === array_key_first($sorts) ? null : $key])) }}" role="menuitemradio" aria-checked="{{ $sort === $key ? 'true' : 'false' }}"
                           class="flex items-center justify-between rounded-lg px-2 py-1.5 text-[13px] hover:bg-subdued">
                            {{ $label }} @if ($sort === $key)<x-icon name="check" class="size-4" />@endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </form>
</div>
