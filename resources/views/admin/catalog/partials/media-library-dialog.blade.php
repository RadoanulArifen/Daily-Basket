{{-- "Choose from library" dialog. Lives inside an x-data using mediaMixin (productEditor or mediaField). --}}
<template x-teleport="body">
    <div x-show="library.open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-0 sm:items-center sm:p-4"
         @keydown.escape.window="library.open = false" role="dialog" aria-modal="true" aria-labelledby="library-title">
        <div x-show="library.open" x-transition @click.outside="library.open = false" x-trap.noscroll="library.open"
             class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-t-2xl bg-surface shadow-xl sm:rounded-2xl">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 id="library-title" class="text-base font-semibold">{{ __('admin.media.choose_title') }}</h2>
                <button type="button" @click="library.open = false" class="rounded-lg p-1.5 text-muted hover:bg-ink/5" aria-label="{{ __('admin.common.close') }}">
                    <x-icon name="x" class="size-5" />
                </button>
            </div>

            <div class="border-b border-line px-5 py-3">
                <label class="relative block">
                    <span class="sr-only">{{ __('admin.media.search') }}</span>
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    <input type="search" x-model="library.q" @input.debounce.400ms="loadLibrary(true)" placeholder="{{ __('admin.media.search') }}" class="admin-field ps-9">
                </label>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <p x-show="! library.loading && ! library.items.length" class="py-10 text-center text-sm text-muted">{{ __('admin.media.library_empty') }}</p>
                <ul class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
                    <template x-for="item in library.items" :key="item.id">
                        <li>
                            <button type="button" @click="toggleLibraryItem(item)" :aria-pressed="isSelectedInLibrary(item)"
                                    class="group relative block aspect-square w-full overflow-hidden rounded-lg border-2 bg-canvas transition"
                                    :class="isSelectedInLibrary(item) ? 'border-brand-600' : 'border-transparent hover:border-line-strong'">
                                <img :src="item.thumb" :alt="item.alt || item.name" class="size-full object-cover" loading="lazy">
                                <span x-show="isSelectedInLibrary(item)" class="absolute end-1.5 top-1.5 flex size-5 items-center justify-center rounded-full bg-brand-600 text-white">
                                    <x-icon name="check" class="size-3.5" />
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
                <div class="mt-4 text-center" x-show="library.next">
                    <x-admin.button type="button" variant="secondary" size="sm" x-on:click="loadLibrary()" x-bind:disabled="library.loading">{{ __('admin.media.load_more') }}</x-admin.button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-3">
                <x-admin.button type="button" variant="secondary" x-on:click="library.open = false">{{ __('admin.common.cancel') }}</x-admin.button>
                <x-admin.button type="button" x-on:click="addFromLibrary()" x-bind:disabled="! library.selected.length">
                    {{ __('admin.media.add_selected') }} <span x-show="library.selected.length" x-text="'(' + library.selected.length + ')'"></span>
                </x-admin.button>
            </div>
        </div>
    </div>
</template>
