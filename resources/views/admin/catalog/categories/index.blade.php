@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.categories.title'))

@section('content')
    <x-admin.page-header :title="__('admin.categories.title')" :subtitle="__('admin.categories.subtitle')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.categories.create')" icon="plus">{{ __('admin.categories.add') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($categories->isEmpty())
            <x-admin.empty-state icon="folder-tree" :title="__('admin.categories.empty_title')" :text="__('admin.categories.empty_text')">
                <x-admin.button :href="route('admin.categories.create')" icon="plus">{{ __('admin.categories.add') }}</x-admin.button>
            </x-admin.empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($categories as $category)
                    <li class="flex items-center gap-3 py-3 pe-4 transition hover:bg-canvas/50 sm:pe-5" style="padding-inline-start: {{ 1 + $category->depth * 1.75 }}rem">
                        @if ($category->depth > 0)
                            <span class="h-5 w-3 shrink-0 border-b border-s border-line-strong rtl:rounded-bl-none" aria-hidden="true"></span>
                        @endif
                        <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-canvas">
                            @if ($category->image)
                                <img src="{{ $category->image->url('thumb') }}" alt="" class="size-full object-cover" loading="lazy">
                            @else
                                <x-icon name="folder-tree" class="size-4 text-muted" />
                            @endif
                        </span>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium hover:underline">{{ $category->name }}</span>
                            <span class="block truncate text-[13px] text-muted" dir="ltr">/{{ $category->slug }}</span>
                        </a>
                        <span class="hidden text-sm text-ink-soft sm:block">{{ trans_choice('admin.categories.product_count', $category->products_count, ['count' => $category->products_count]) }}</span>
                        @unless ($category->is_active)
                            <x-admin.badge>{{ __('admin.common.hidden') }}</x-admin.badge>
                        @endunless
                        <x-admin.confirm-delete icon-only :action="route('admin.categories.destroy', $category)"
                            :title="__('admin.categories.delete_title', ['name' => $category->name])"
                            :message="__('admin.categories.delete_text')"
                            :label="__('admin.common.delete').' '.$category->name" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
