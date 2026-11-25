@extends('admin.layouts.app')

@section('title', __('admin.products.title'))
@section('fullWidth', true)

@section('content')
    <x-admin.page-header :title="__('admin.products.title')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.products.export')" variant="secondary">{{ __('admin.import.export') }}</x-admin.button>
            <x-admin.button :href="route('admin.products.import')" variant="secondary">{{ __('admin.import.import') }}</x-admin.button>
            <x-admin.button :href="route('admin.products.create')">{{ __('admin.products.add') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @php $status = $filters['status'] ?? null; $hasFilters = collect($filters)->except(['status', 'sort'])->filter()->isNotEmpty(); @endphp

    {{-- Bulk selection state. (@js can't be used inside a component tag's attributes.) --}}
    <div x-data="indexFilters({ ids: @js($products->pluck('id')) })" class="overflow-hidden rounded-xl bg-surface shadow-card">
        <x-admin.index-filters resource="products" route="admin.products.index" tab-param="status" :placeholder="__('admin.products.search')"
            :tabs="[
                ['key' => 'all', 'label' => __('admin.products.tabs.all')],
                ['key' => 'active', 'label' => __('admin.products.tabs.active'), 'count' => $statusCounts['active'] ?? 0],
                ['key' => 'draft', 'label' => __('admin.products.tabs.draft'), 'count' => $statusCounts['draft'] ?? 0],
                ['key' => 'archived', 'label' => __('admin.products.tabs.archived')],
            ]"
            :sorts="collect(\App\Http\Controllers\Admin\Catalog\ProductController::SORTS)->mapWithKeys(fn ($s) => [$s => __('admin.products.sort.'.$s)])->all()">
            <x-slot:filters>
                <x-admin.filter-pill name="category" :label="__('admin.products.filters.category')" :options="$categories->mapWithKeys(fn ($c) => [$c->id => str_repeat('— ', $c->depth).$c->name])->all()" />
                <x-admin.filter-pill name="stock" :label="__('admin.products.filters.stock')" :options="['in' => __('admin.products.stock_filter.in'), 'low' => __('admin.products.stock_filter.low'), 'out' => __('admin.products.stock_filter.out')]" />
                <x-admin.filter-pill name="featured" :label="__('admin.products.filters.featured')" :options="['yes' => __('admin.common.yes')]" />
            </x-slot:filters>
        </x-admin.index-filters>

        @if ($products->isEmpty())
            @if ($hasFilters || $status)
                <x-admin.empty-state icon="search" :title="__('admin.products.no_results')" :text="__('admin.products.no_results_text')">
                    <x-admin.button :href="route('admin.products.index')" variant="secondary">{{ __('admin.common.clear_filters') }}</x-admin.button>
                </x-admin.empty-state>
            @else
                <x-admin.empty-state icon="package" :title="__('admin.products.empty_title')" :text="__('admin.products.empty_text')">
                    <x-admin.button :href="route('admin.products.create')" icon="plus">{{ __('admin.products.add') }}</x-admin.button>
                </x-admin.empty-state>
            @endif
        @else
            {{-- Bulk actions --}}
            <form x-show="selected.length" x-cloak method="POST" action="{{ route('admin.products.bulk') }}"
                  class="flex flex-wrap items-center gap-2 border-b border-line bg-subdued px-3 py-2"
                  x-data="{ action: '' }" @submit="if (action === 'delete' && ! confirm(@js(__('admin.products.bulk_delete_confirm')))) $event.preventDefault()">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <span class="me-1 text-[13px] font-semibold" x-text="@js(__('admin.products.selected')).replace(':count', selected.length)"></span>
                <label class="sr-only" for="bulk-action">{{ __('admin.common.actions') }}</label>
                <select id="bulk-action" name="action" x-model="action" required class="admin-field w-auto">
                    <option value="">{{ __('admin.products.bulk_choose') }}</option>
                    <option value="active">{{ __('admin.products.bulk.active') }}</option>
                    <option value="draft">{{ __('admin.products.bulk.draft') }}</option>
                    <option value="archived">{{ __('admin.products.bulk.archived') }}</option>
                    <option value="delete">{{ __('admin.products.bulk.delete') }}</option>
                </select>
                <x-admin.button variant="secondary" size="sm">{{ __('admin.common.apply') }}</x-admin.button>
                <button type="button" @click="clear()" class="ms-auto text-[13px] font-medium text-brand-600 hover:underline">{{ __('admin.common.clear_selection') }}</button>
            </form>

            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10"><input type="checkbox" class="admin-checkbox" :checked="all" :indeterminate="some" @change="toggleAll()" aria-label="{{ __('admin.common.select_all') }}"></th>
                            <th class="w-12"><span class="sr-only">{{ __('admin.products.image') }}</span></th>
                            <th>{{ __('admin.products.cols.product') }}</th>
                            <th>{{ __('admin.products.cols.status') }}</th>
                            <th>{{ __('admin.products.cols.inventory') }}</th>
                            <th>{{ __('admin.products.cols.category') }}</th>
                            <th class="text-end">{{ __('admin.products.cols.price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $url = route('admin.products.edit', $product);
                                $tracked = $product->variants->where('track_inventory', true);
                                $stock = $tracked->sum('stock_quantity');
                                $prices = $product->variants->map(fn ($v) => $v->price);
                                $min = $prices->sortBy('minor')->first();
                                $max = $prices->sortByDesc('minor')->first();
                                $image = $product->media->first();
                            @endphp
                            <tr :data-selected="selected.includes({{ $product->id }})" class="cursor-pointer" x-data @click="if (! $event.target.closest('a,input,button')) window.location = @js($url)">
                                <td><input type="checkbox" class="admin-checkbox" value="{{ $product->id }}" x-model.number="selected" aria-label="{{ __('admin.common.select_row', ['name' => $product->name]) }}"></td>
                                <td>
                                    @if ($image)
                                        <img src="{{ $image->url('thumb') }}" alt="" loading="lazy" class="size-10 rounded-lg border border-line object-cover">
                                    @else
                                        <span class="flex size-10 items-center justify-center rounded-lg border border-line bg-subdued text-muted"><x-icon name="image" class="size-4" /></span>
                                    @endif
                                </td>
                                <td class="max-w-72"><a href="{{ $url }}" class="block truncate font-semibold text-ink-strong hover:underline">{{ $product->name }}</a></td>
                                <td>
                                    <x-admin.badge :tone="match ($product->status->value) { 'active' => 'success', 'draft' => 'info', default => 'neutral' }">{{ $product->status->label() }}</x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($tracked->isEmpty())
                                        <span class="text-muted">{{ __('admin.products.not_tracked') }}</span>
                                    @else
                                        <span @class(['text-danger-600' => $stock <= 0, 'text-ink-soft' => $stock > 0])>
                                            {{ trans_choice('admin.products.in_stock_for', $product->variants->count(), ['stock' => $stock, 'count' => $product->variants->count()]) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-40 truncate text-ink-soft">{{ $product->categories->pluck('name')->implode(', ') ?: '—' }}</td>
                                <td class="text-end whitespace-nowrap" dir="ltr">{{ $min ? price_admin($min) : '—' }}@if ($min && $max && $max->minor !== $min->minor) – {{ price_admin($max) }}@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links('components.admin.pagination') }}
        @endif
    </div>
@endsection
