@extends('admin.layouts.app')

@section('title', $category->exists ? $category->name : __('admin.categories.add'))

@section('content')
    @php
        $image = old('image_id', $category->image_id)
            ? \App\Models\Media::find(old('image_id', $category->image_id))?->toPickerArray()
            : null;
        $mediaConfig = [
            'media' => $image ? [$image] : [],
            'multiple' => false,
            'uploadUrl' => route('admin.media.store'),
            'libraryUrl' => route('admin.media.index', ['format' => 'json']),
            'messages' => ['type' => __('admin.media.errors.type'), 'size' => __('admin.media.errors.size'), 'failed' => __('admin.media.upload_failed')],
        ];
    @endphp

    <x-admin.page-header :title="$category->exists ? $category->name : __('admin.categories.add')" :back="route('admin.categories.index')" />

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.categories.details_title')" :description="__('admin.categories.details_text')">
            <x-admin.translatable-input name="name" :label="__('admin.categories.fields.name')" :values="$category->getTranslations('name')" required maxlength="100" />
            <x-admin.translatable-input name="description" :label="__('admin.categories.fields.description')" :values="$category->getTranslations('description')" rich :rows="4" :help="__('admin.categories.fields.description_help')" />
            <x-admin.select name="parent_id" :label="__('admin.categories.fields.parent')" :value="$category->parent_id" :placeholder="__('admin.categories.no_parent')"
                :options="$parents->mapWithKeys(fn ($p) => [$p->id => str_repeat('— ', $p->depth).$p->name])->all()" :help="__('admin.categories.fields.parent_help', ['max' => \App\Models\Category::MAX_DEPTH])" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.categories.image_title')" :description="__('admin.categories.image_text')">
            <div x-data="mediaField(@js($mediaConfig))" class="space-y-4">
                <input type="hidden" name="image_id" :value="media[0]?.id ?? ''">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex size-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-line-strong bg-canvas">
                        <template x-if="media[0]"><img :src="media[0].url" alt="" class="size-full object-cover"></template>
                        <template x-if="! media[0]"><x-icon name="image" class="size-6 text-muted" /></template>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-line-strong bg-surface px-3.5 text-sm font-medium shadow-xs hover:bg-canvas">
                            <x-icon name="image" class="size-4" /> {{ __('admin.media.upload') }}
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="pickFiles($event)">
                        </label>
                        <x-admin.button type="button" variant="secondary" x-on:click="openLibrary()">{{ __('admin.media.choose_existing') }}</x-admin.button>
                        <x-admin.button type="button" variant="ghost" x-show="media[0]" x-on:click="media = []">{{ __('admin.common.remove') }}</x-admin.button>
                    </div>
                </div>
                @include('admin.catalog.partials.upload-list')
                @include('admin.catalog.partials.media-library-dialog')
                <x-admin.field-error name="image_id" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.categories.visibility_title')" :description="__('admin.categories.visibility_text')">
            <x-admin.toggle name="is_active" :label="__('admin.categories.fields.active')" :description="__('admin.categories.fields.active_help')" :checked="$category->is_active ?? true" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.input name="sort_order" type="number" min="0" max="9999" :label="__('admin.common.sort_order')" :value="$category->sort_order ?? 0" :help="__('admin.common.sort_order_help')" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.products.seo_title')" :description="__('admin.categories.seo_text')">
            <x-admin.translatable-input name="seo_title" :label="__('admin.products.fields.seo_title')" :values="$category->getTranslations('seo_title')" maxlength="70" />
            <x-admin.translatable-input name="seo_description" :label="__('admin.products.fields.seo_description')" :values="$category->getTranslations('seo_description')" multiline :rows="2" maxlength="160" />
            <x-admin.input name="slug" :label="__('admin.common.url_handle')" :value="$category->slug" maxlength="190" dir="ltr" prefix="/collections/" :help="__('admin.common.url_handle_help')" />
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $category->exists])
    </form>

    @if ($category->exists)
        <div class="mt-6">
            <x-admin.confirm-delete :action="route('admin.categories.destroy', $category)" :label="__('admin.categories.delete')"
                :title="__('admin.categories.delete_title', ['name' => $category->name])" :message="__('admin.categories.delete_text')" />
        </div>
    @endif
@endsection
