@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.returns.title'))

@section('content')
    @php
        $tabs = collect(['open', 'requested', 'approved', 'received', 'completed', 'rejected'])->map(fn ($s) => [
            'label' => __("admin.returns.tabs.{$s}"), 'url' => route('admin.returns.index', ['status' => $s]), 'active' => $status === $s,
            'count' => $s === 'requested' ? $waiting : null,
        ])->all();
    @endphp

    <x-admin.page-header :title="__('admin.returns.title')" :subtitle="__('admin.returns.subtitle')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.returns.settings') }}" class="flex flex-wrap items-center gap-2 text-sm">
                @csrf @method('PUT')
                <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked($enabled) class="size-4 accent-brand-600"> {{ __('admin.returns.accept') }}</label>
                <label for="window_days" class="text-ink-soft">{{ __('admin.returns.window') }}</label>
                <input id="window_days" type="number" name="window_days" min="0" max="365" value="{{ $days }}" class="admin-field w-20 py-1.5" dir="ltr">
                <x-admin.button variant="secondary" size="sm">{{ __('admin.common.save') }}</x-admin.button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="border-b border-line px-2 sm:px-3"><x-admin.tabs :tabs="$tabs" /></div>
        @if ($returns->isEmpty())
            <x-admin.empty-state icon="rotate-ccw" :title="__('admin.returns.empty_title')" :text="__('admin.returns.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($returns as $return)
                    <li>
                        <a href="{{ route('admin.returns.show', $return) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 hover:bg-canvas/50 sm:px-5">
                            <span class="w-20 text-sm font-semibold" dir="ltr">{{ $return->number }}</span>
                            <span class="w-24 text-[13px] text-ink-soft" dir="ltr">{{ $return->order->number }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $return->order->ship_name }} <span class="text-muted">· {{ __("store.returns.reasons.{$return->reason}") }} · {{ trans_choice('admin.orders.item_count', (int) $return->units, ['count' => (int) $return->units]) }}</span></span>
                            <x-admin.badge>{{ __("store.returns.resolutions.{$return->resolution}") }}</x-admin.badge>
                            <x-admin.badge :tone="$return->tone()">{{ __("admin.returns.status.{$return->status}") }}</x-admin.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
            {{ $returns->links('components.admin.pagination') }}
        @endif
    </x-admin.card>
@endsection
