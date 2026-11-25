@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.media.title'))

@section('content')
    @php
        $config = [
            'media' => [],
            'multiple' => true,
            'uploadUrl' => route('admin.media.store'),
            'libraryUrl' => route('admin.media.index', ['format' => 'json']),
            'messages' => ['type' => __('admin.media.errors.type'), 'size' => __('admin.media.errors.size'), 'failed' => __('admin.media.upload_failed')],
        ];
    @endphp

    <div x-data="mediaField(@js($config))" @media-uploaded="if (! uploads.length) setTimeout(() => window.location.reload(), 300)">
        <x-admin.page-header :title="__('admin.media.title')" :subtitle="__('admin.media.subtitle')">
            <x-slot:actions>
                <label class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg bg-brand-600 px-3.5 text-sm font-medium text-white shadow-xs hover:bg-brand-700">
                    <x-icon name="plus" class="size-4" /> {{ __('admin.media.upload') }}
                    <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pickFiles($event)">
                </label>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="mb-4">@include('admin.catalog.partials.upload-list')</div>

        <x-admin.card :padding="false">
            <form method="GET" class="border-b border-line p-3 sm:px-4">
                <label class="relative block">
                    <span class="sr-only">{{ __('admin.media.search') }}</span>
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    <input type="search" name="q" value="{{ $term }}" placeholder="{{ __('admin.media.search') }}" class="admin-field ps-9">
                </label>
            </form>

            @if ($media->isEmpty())
                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dropFiles($event)"
                       class="m-4 flex cursor-pointer flex-col items-center rounded-lg border-2 border-dashed px-6 py-12 text-center"
                       :class="dragging ? 'border-brand-600 bg-brand-50' : 'border-line-strong'">
                    <x-icon name="image" class="size-8 text-muted" />
                    <span class="mt-3 text-[15px] font-semibold">{{ $term ? __('admin.media.no_results') : __('admin.media.empty_title') }}</span>
                    <span class="mt-1 text-sm text-muted">{{ __('admin.media.drop_help') }}</span>
                    <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pickFiles($event)">
                </label>
            @else
                <ul class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dropFiles($event)">
                    @foreach ($media as $item)
                        <li class="overflow-hidden rounded-lg border border-line" x-data="{ editing: false }">
                            <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="block aspect-square bg-canvas">
                                <img src="{{ $item->url('thumb') }}" alt="{{ $item->getTranslation('alt', 'en', false) }}" class="size-full object-cover" loading="lazy">
                            </a>
                            <div class="space-y-1 p-2.5">
                                <p class="truncate text-[13px] font-medium" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                                <p class="text-xs text-muted" dir="ltr">{{ $item->width }}×{{ $item->height }} · {{ number_format($item->size / 1024) }} KB</p>
                                <p class="text-xs text-muted">{{ trans_choice('admin.media.used_in', $item->products_count, ['count' => $item->products_count]) }}</p>
                                <div class="flex items-center gap-1 pt-1">
                                    <button type="button" @click="editing = true" class="rounded-md px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">{{ __('admin.media.edit_alt') }}</button>
                                    <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText(@js($item->url('large'))); copied = true; setTimeout(() => copied = false, 1500)"
                                            class="rounded-md px-2 py-1 text-xs text-muted hover:bg-canvas hover:text-ink">
                                        <span x-show="! copied">{{ __('admin.media.copy_link') }}</span><span x-show="copied" x-cloak>{{ __('admin.media.copied') }}</span>
                                    </button>
                                    <span class="ms-auto">
                                        <x-admin.confirm-delete icon-only :action="route('admin.media.destroy', $item)"
                                            :title="__('admin.media.delete_title')"
                                            :message="$item->products_count ? trans_choice('admin.media.delete_used', $item->products_count, ['count' => $item->products_count]) : __('admin.media.delete_text')" />
                                    </span>
                                </div>
                            </div>

                            <template x-teleport="body">
                                <div x-show="editing" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" @keydown.escape.window="editing = false" role="dialog" aria-modal="true">
                                    <form method="POST" action="{{ route('admin.media.update', $item) }}" @click.outside="editing = false" x-trap.noscroll="editing" class="w-full max-w-md space-y-4 rounded-2xl bg-surface p-6 shadow-xl">
                                        @csrf
                                        @method('PUT')
                                        <h2 class="text-base font-semibold">{{ __('admin.media.alt_title') }}</h2>
                                        <p class="text-sm text-muted">{{ __('admin.media.alt_help') }}</p>
                                        <label class="block space-y-1.5">
                                            <span class="text-sm font-medium">English</span>
                                            <input type="text" name="alt[en]" value="{{ $item->getTranslation('alt', 'en', false) }}" maxlength="200" class="admin-field">
                                        </label>
                                        @if (app(\App\Support\Localization::class)->enabled('ar'))
                                        <label class="block space-y-1.5">
                                            <span class="text-sm font-medium" lang="ar">العربية</span>
                                            <input type="text" name="alt[ar]" value="{{ $item->getTranslation('alt', 'ar', false) }}" maxlength="200" dir="rtl" lang="ar" class="admin-field">
                                        </label>
                                        @endif
                                        <div class="flex justify-end gap-2">
                                            <x-admin.button type="button" variant="secondary" x-on:click="editing = false">{{ __('admin.common.cancel') }}</x-admin.button>
                                            <x-admin.button>{{ __('admin.common.save') }}</x-admin.button>
                                        </div>
                                    </form>
                                </div>
                            </template>
                        </li>
                    @endforeach
                </ul>
                {{ $media->links('components.admin.pagination') }}
            @endif
        </x-admin.card>
    </div>
@endsection
