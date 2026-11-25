@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.subscribers.title'))

@section('content')
    @php
        $tabs = [
            ['label' => __('admin.subscribers.subscribed'), 'url' => route('admin.subscribers.index'), 'active' => $showing === 'subscribed', 'count' => $counts['subscribed']],
            ['label' => __('admin.subscribers.unsubscribed'), 'url' => route('admin.subscribers.index', ['status' => 'unsubscribed']), 'active' => $showing === 'unsubscribed', 'count' => $counts['unsubscribed']],
        ];
    @endphp

    <x-admin.page-header :title="__('admin.subscribers.title')" :subtitle="__('admin.subscribers.subtitle')">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.subscribers.export', ['status' => $showing])" icon="download">{{ __('admin.subscribers.export') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false" class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-2 sm:px-3">
            <x-admin.tabs :tabs="$tabs" />
            <form method="GET" class="p-2">
                @if ($showing === 'unsubscribed')<input type="hidden" name="status" value="unsubscribed">@endif
                <input name="q" value="{{ $term }}" placeholder="{{ __('admin.subscribers.search') }}" class="admin-field w-56 py-1.5 text-[13px]" dir="ltr">
            </form>
        </div>

        @if ($subscribers->isEmpty())
            <x-admin.empty-state icon="mail" :title="__('admin.subscribers.empty_title')" :text="__('admin.subscribers.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($subscribers as $subscriber)
                    <li class="flex flex-wrap items-center gap-x-5 gap-y-1 px-4 py-3 sm:px-5">
                        <span class="min-w-56 flex-1 text-[14px]" dir="ltr">{{ $subscriber->email }}</span>
                        <span class="text-[13px] text-muted">{{ __('admin.subscribers.source_'.($subscriber->source ?: 'unknown')) }}</span>
                        <span class="text-[13px] text-muted">{{ $subscriber->created_at?->translatedFormat('j M Y') }}</span>
                        <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}" x-data
                              @submit="if (! confirm(@js(__('admin.subscribers.remove_confirm')))) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button class="text-[13px] text-muted underline">{{ __('admin.common.delete') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3">{{ $subscribers->links() }}</div>
        @endif
    </x-admin.card>
@endsection
