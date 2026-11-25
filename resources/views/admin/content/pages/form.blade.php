@extends('admin.layouts.app')

@section('title', $page->exists ? $page->title : __('admin.pages.add'))

@section('content')
    @php
        $pictures = $page->images()->map->toPickerArray()->values()->all();
        $mediaConfig = [
            'media' => $pictures, 'multiple' => true,
            'uploadUrl' => route('admin.media.store'), 'libraryUrl' => route('admin.media.index', ['format' => 'json']),
            'messages' => ['type' => __('admin.media.errors.type'), 'size' => __('admin.media.errors.size'), 'failed' => __('admin.media.upload_failed')],
        ];
    @endphp
    <x-admin.page-header :title="$page->exists ? $page->title : __('admin.pages.add')" :back="route('admin.pages.index')">
        @if ($page->exists)
            <x-slot:actions><x-admin.button :href="store_route('pages.show', ['slug' => $page->slug])" variant="secondary" icon="external-link" target="_blank">{{ __('admin.common.view') }}</x-admin.button></x-slot:actions>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.pages.content_title')" :description="__('admin.pages.content_text')">
            <x-admin.translatable-input name="title" :label="__('admin.pages.fields.title')" :values="$page->getTranslations('title')" required maxlength="150" />
            <x-admin.translatable-input name="eyebrow" :label="__('admin.pages.fields.eyebrow')" :values="$page->getTranslations('eyebrow')" maxlength="80" :help="__('admin.pages.fields.eyebrow_help')" />
            <x-admin.translatable-input name="heading" :label="__('admin.pages.fields.heading')" :values="$page->getTranslations('heading')" maxlength="200" :help="__('admin.pages.fields.heading_help')" />
            <x-admin.translatable-input name="intro" :label="__('admin.pages.fields.intro')" :values="$page->getTranslations('intro')" multiline :rows="3" maxlength="1000" :help="__('admin.pages.fields.intro_help')" />
            <x-admin.translatable-input name="body" :label="__('admin.pages.fields.body')" :values="$page->getTranslations('body')" rich :rows="16" :help="__('admin.pages.body_help')" />
            <x-admin.translatable-input name="footnote" :label="__('admin.pages.fields.footnote')" :values="$page->getTranslations('footnote')" maxlength="500" :help="__('admin.pages.fields.footnote_help')" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.pages.images_title')" :description="__('admin.pages.images_text')">
            <div x-data="mediaField(@js($mediaConfig))" class="space-y-4">
                <template x-for="item in media" :key="item.id"><input type="hidden" name="image_ids[]" :value="item.id"></template>
                <div class="flex flex-wrap gap-3">
                    <template x-for="(item, index) in media" :key="item.id">
                        <div class="relative h-28 w-40 overflow-hidden rounded-lg border border-line bg-canvas">
                            <img :src="item.url" alt="" class="size-full object-cover">
                            <span class="absolute start-1.5 top-1.5 rounded bg-surface/90 px-1.5 text-xs font-medium" x-text="index + 1"></span>
                            <button type="button" class="absolute end-1.5 top-1.5 rounded bg-surface/90 px-1.5 text-xs" x-on:click="removeMedia(item.id)">{{ __('admin.common.remove') }}</button>
                        </div>
                    </template>
                </div>
                <div class="flex flex-wrap gap-2" x-show="media.length < 3">
                    <label class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-line-strong bg-surface px-3.5 text-sm font-medium shadow-xs hover:bg-canvas">
                        <x-icon name="image" class="size-4" /> {{ __('admin.media.upload') }}
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pickFiles($event)">
                    </label>
                    <x-admin.button type="button" variant="secondary" x-on:click="openLibrary()">{{ __('admin.media.choose_existing') }}</x-admin.button>
                </div>
                @include('admin.catalog.partials.upload-list')
                @include('admin.catalog.partials.media-library-dialog')
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.pages.options_title')">
            <x-admin.toggle name="is_active" :label="__('admin.pages.fields.active')" :checked="$page->is_active ?? true" />
            <x-admin.toggle name="show_contact_form" :label="__('admin.pages.fields.contact_form')" :description="__('admin.pages.fields.contact_form_help')" :checked="$page->show_contact_form" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.products.seo_title')">
            <x-admin.translatable-input name="seo_title" :label="__('admin.products.fields.seo_title')" :values="$page->getTranslations('seo_title')" maxlength="70" />
            <x-admin.translatable-input name="seo_description" :label="__('admin.products.fields.seo_description')" :values="$page->getTranslations('seo_description')" multiline :rows="2" maxlength="160" />
            <x-admin.input name="slug" :label="__('admin.common.url_handle')" :value="$page->slug" maxlength="190" dir="ltr" prefix="/pages/" :help="__('admin.pages.slug_help')" />
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $page->exists])
    </form>

    @if ($page->exists)
        <div class="mt-6"><x-admin.confirm-delete :action="route('admin.pages.destroy', $page)" :label="__('admin.pages.delete')" :title="__('admin.pages.delete_title', ['name' => $page->title])" :message="__('admin.pages.delete_text')" /></div>
    @endif
@endsection
