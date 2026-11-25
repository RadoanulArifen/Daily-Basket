@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.inventory.title'))

@section('content')
    @php
        $stock = $filters['stock'] ?? null;
        $tabUrl = fn ($s) => route('admin.inventory.index', array_filter([...request()->except(['stock', 'page']), 'stock' => $s]));
        $tabs = [
            ['label' => __('admin.common.all'), 'url' => $tabUrl(null), 'active' => $stock === null],
            ['label' => __('admin.inventory.low'), 'url' => $tabUrl('low'), 'active' => $stock === 'low'],
            ['label' => __('admin.inventory.out'), 'url' => $tabUrl('out'), 'active' => $stock === 'out'],
        ];
    @endphp

    <x-admin.page-header :title="__('admin.inventory.title')" :subtitle="__('admin.inventory.subtitle')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.inventory.threshold') }}" class="flex items-center gap-2 text-sm">
                @csrf
                @method('PUT')
                <label for="threshold" class="text-ink-soft">{{ __('admin.inventory.threshold') }}</label>
                <input id="threshold" type="number" name="low_stock_threshold" min="0" max="10000" value="{{ $threshold }}" class="admin-field w-20 py-1.5" dir="ltr">
                <x-admin.button variant="secondary" size="sm">{{ __('admin.common.save') }}</x-admin.button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="border-b border-line px-2 sm:px-3"><x-admin.tabs :tabs="$tabs" /></div>
        <form method="GET" class="border-b border-line p-3 sm:px-4">
            @if ($stock)<input type="hidden" name="stock" value="{{ $stock }}">@endif
            <label class="relative block">
                <span class="sr-only">{{ __('admin.inventory.search') }}</span>
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('admin.inventory.search') }}" class="admin-field ps-9">
            </label>
        </form>

        @if ($variants->isEmpty())
            <x-admin.empty-state icon="warehouse" :title="$stock || filled($filters['q'] ?? null) ? __('admin.inventory.no_results') : __('admin.inventory.empty_title')" :text="__('admin.inventory.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($variants as $variant)
                    @php
                        $qty = $variant->stock_quantity;
                        $tone = $qty <= 0 ? 'danger' : ($qty <= $threshold ? 'warning' : 'success');
                        $title = $variant->title();
                        $image = $variant->product->media->first();
                    @endphp
                    <li x-data="{ adjusting: false, history: false, mode: 'add' }" class="px-4 py-3 sm:px-5">
                        <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4">
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-canvas">
                                @if ($image)<img src="{{ $image->url('thumb') }}" alt="" class="size-full object-cover" loading="lazy">@else<x-icon name="image" class="size-4 text-muted" />@endif
                            </span>
                            <a href="{{ route('admin.products.edit', $variant->product) }}" class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium hover:underline">{{ $variant->product->name }}</span>
                                <span class="block truncate text-[13px] text-muted">
                                    {{ $title }}@if ($title && $variant->sku) · @endif<span dir="ltr">{{ $variant->sku }}</span>
                                </span>
                            </a>
                            <x-admin.badge :tone="$tone">{{ trans_choice('admin.inventory.available', $qty, ['count' => $qty]) }}</x-admin.badge>
                            <div class="flex gap-1">
                                <x-admin.button type="button" variant="secondary" size="sm" x-on:click="adjusting = ! adjusting; history = false">{{ __('admin.inventory.adjust') }}</x-admin.button>
                                <x-admin.button type="button" variant="ghost" size="sm" x-on:click="history = ! history; adjusting = false" :disabled="$variant->movements->isEmpty()">{{ __('admin.inventory.history') }}</x-admin.button>
                            </div>
                        </div>

                        <form x-show="adjusting" x-cloak x-collapse method="POST" action="{{ route('admin.inventory.adjust', $variant) }}" class="mt-3 grid gap-3 rounded-lg bg-canvas/70 p-4 sm:grid-cols-[auto_7rem_minmax(0,1fr)_minmax(0,1.3fr)_auto] sm:items-end">
                            @csrf
                            <fieldset>
                                <legend class="mb-1 text-[12px] text-muted">{{ __('admin.inventory.action') }}</legend>
                                <div class="inline-flex rounded-lg border border-line-strong bg-surface p-0.5">
                                    @foreach (['add', 'remove', 'set'] as $m)
                                        <label class="cursor-pointer rounded-md px-2.5 py-1 text-sm has-[:checked]:bg-brand-600 has-[:checked]:text-white">
                                            <input type="radio" name="mode" value="{{ $m }}" x-model="mode" class="sr-only">{{ __("admin.inventory.modes.{$m}") }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <label class="block space-y-1">
                                <span class="block text-[12px] text-muted">{{ __('admin.inventory.quantity') }}</span>
                                <input type="number" name="quantity" min="0" max="1000000" required class="admin-field py-1.5" dir="ltr">
                            </label>
                            <label class="block space-y-1">
                                <span class="block text-[12px] text-muted">{{ __('admin.inventory.reason') }}</span>
                                <select name="reason" class="admin-field py-1.5">
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block space-y-1">
                                <span class="block text-[12px] text-muted">{{ __('admin.inventory.note') }}</span>
                                <input type="text" name="note" maxlength="255" class="admin-field py-1.5" placeholder="{{ __('admin.inventory.note_placeholder') }}">
                            </label>
                            <x-admin.button size="sm">{{ __('admin.common.save') }}</x-admin.button>
                        </form>

                        @if ($variant->movements->isNotEmpty())
                            <div x-show="history" x-cloak x-collapse class="mt-3 overflow-x-auto rounded-lg border border-line">
                                <table class="w-full text-[13px]">
                                    <thead class="bg-canvas/70 text-muted">
                                        <tr>
                                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.inventory.date') }}</th>
                                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.inventory.change') }}</th>
                                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.inventory.after') }}</th>
                                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.inventory.reason') }}</th>
                                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.inventory.by') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        @foreach ($variant->movements as $movement)
                                            <tr>
                                                <td class="whitespace-nowrap px-3 py-2 text-ink-soft">{{ $movement->created_at->timezone(config('store.display_timezone'))->translatedFormat('j M, H:i') }}</td>
                                                <td @class(['px-3 py-2 font-medium', 'text-success-600' => $movement->change > 0, 'text-danger-600' => $movement->change < 0]) dir="ltr">{{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}</td>
                                                <td class="px-3 py-2">{{ $movement->quantity_after }}</td>
                                                <td class="px-3 py-2 text-ink-soft">{{ $movement->reason->label() }}@if ($movement->note) <span class="text-muted">— {{ $movement->note }}</span>@endif</td>
                                                <td class="px-3 py-2 text-ink-soft">{{ $movement->admin?->name ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            {{ $variants->links('components.admin.pagination') }}
        @endif
    </x-admin.card>
@endsection
