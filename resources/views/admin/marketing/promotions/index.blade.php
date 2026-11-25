@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.promotions.title'))

@section('content')
    <x-admin.page-header :title="__('admin.promotions.title')" :subtitle="__('admin.promotions.subtitle')">
        <x-slot:actions><x-admin.button :href="route('admin.promotions.create')" icon="plus">{{ __('admin.promotions.add') }}</x-admin.button></x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($promotions->isEmpty())
            <x-admin.empty-state icon="tag" :title="__('admin.promotions.empty_title')" :text="__('admin.promotions.empty_text')">
                <x-admin.button :href="route('admin.promotions.create')" icon="plus">{{ __('admin.promotions.add') }}</x-admin.button>
            </x-admin.empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($promotions as $promotion)
                    <li>
                        <a href="{{ route('admin.promotions.edit', $promotion) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 hover:bg-canvas/50 sm:px-5">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $promotion->label() }}</span>
                                <span class="block text-[13px] text-muted">{{ __("admin.promotions.scopes.{$promotion->applies_to}") }}@if ($promotion->category) · {{ $promotion->category->name }}@endif @if ($promotion->applies_to === 'products') · {{ trans_choice('admin.promotions.product_count', count((array) $promotion->product_ids), ['count' => count((array) $promotion->product_ids)]) }}@endif</span>
                            </span>
                            <x-admin.badge :tone="$promotion->isLive() ? 'success' : 'neutral'">{{ $promotion->isLive() ? __('admin.banners.live') : __('admin.banners.not_live') }}</x-admin.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
