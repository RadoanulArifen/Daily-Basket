@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.menus.title'))

@section('content')
    <x-admin.page-header :title="__('admin.menus.title')" :subtitle="__('admin.menus.subtitle')" />
    <x-admin.card data-tour="menus-list" :padding="false">
        <ul class="divide-y divide-line">
            @foreach ($menus as $menu)
                <li>
                    <a href="{{ route('admin.menus.edit', $menu) }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-canvas/50 sm:px-5">
                        <x-icon name="navigation" class="size-4 text-muted" />
                        <span class="min-w-0 flex-1 text-sm font-medium">{{ __("admin.menus.handles.{$menu->handle}") !== "admin.menus.handles.{$menu->handle}" ? __("admin.menus.handles.{$menu->handle}") : $menu->name }}</span>
                        <span class="text-[13px] text-muted">{{ trans_choice('admin.menus.item_count', $menu->items_count, ['count' => $menu->items_count]) }}</span>
                        <x-icon name="chevron-right" class="size-4 text-muted rtl:rotate-180" />
                    </a>
                </li>
            @endforeach
        </ul>
    </x-admin.card>
@endsection
