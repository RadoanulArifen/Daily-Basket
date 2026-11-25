@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.banners.title'))

@section('content')
    <x-admin.page-header :title="__('admin.banners.title')" :subtitle="__('admin.banners.subtitle')">
        <x-slot:actions><x-admin.button :href="route('admin.banners.create')" icon="plus">{{ __('admin.banners.add') }}</x-admin.button></x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($banners->isEmpty())
            <x-admin.empty-state icon="image" :title="__('admin.banners.empty_title')" :text="__('admin.banners.empty_text')">
                <x-admin.button :href="route('admin.banners.create')" icon="plus">{{ __('admin.banners.add') }}</x-admin.button>
            </x-admin.empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($banners as $banner)
                    @php
                        $live = $banner->is_active && (! $banner->starts_at || $banner->starts_at->isPast()) && (! $banner->ends_at || $banner->ends_at->isFuture());
                    @endphp
                    <li>
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="flex items-center gap-4 px-4 py-3 hover:bg-canvas/50 sm:px-5">
                            <span class="flex h-12 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-canvas">
                                @if ($banner->image)<img src="{{ $banner->image->url('thumb') }}" alt="" class="size-full object-cover">@else<x-icon name="image" class="size-4 text-muted" />@endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $banner->title }}</span>
                                <span class="block text-[13px] text-muted">{{ \App\Models\Banner::placementLabel($banner->placement) }}@if ($banner->ends_at) · {{ __('admin.banners.until', ['date' => $banner->ends_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y')]) }}@endif</span>
                            </span>
                            <x-admin.badge :tone="$live ? 'success' : 'neutral'">{{ $live ? __('admin.banners.live') : __('admin.banners.not_live') }}</x-admin.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
