@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.pages.title'))

@section('content')
    <x-admin.page-header :title="__('admin.pages.title')" :subtitle="__('admin.pages.subtitle')">
        <x-slot:actions><x-admin.button :href="route('admin.pages.create')" icon="plus">{{ __('admin.pages.add') }}</x-admin.button></x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($pages->isEmpty())
            <x-admin.empty-state icon="file-text" :title="__('admin.pages.empty_title')" :text="__('admin.pages.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($pages as $page)
                    <li class="flex items-center gap-4 px-4 py-3 sm:px-5">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium hover:underline">{{ $page->title }}</span>
                            <span class="block truncate text-[13px] text-muted" dir="ltr">/pages/{{ $page->slug }}</span>
                        </a>
                        @if ($page->show_contact_form)<x-admin.badge tone="info">{{ __('admin.pages.has_form') }}</x-admin.badge>@endif
                        @unless ($page->is_active)<x-admin.badge>{{ __('admin.common.hidden') }}</x-admin.badge>@endunless
                        <a href="{{ store_route('pages.show', ['slug' => $page->slug]) }}" target="_blank" class="rounded-lg p-1.5 text-muted hover:bg-ink/5 hover:text-ink" aria-label="{{ __('admin.common.view') }}"><x-icon name="external-link" class="size-4" /></a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
