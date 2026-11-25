@extends('admin.layouts.app')

@section('title', $banner->exists ? $banner->title : __('admin.banners.add'))

@section('content')
    @php
        $tz = config('store.display_timezone');
        $local = fn ($date) => $date?->timezone($tz)->format('Y-m-d\TH:i');
        $image = old('image_id', $banner->image_id) ? \App\Models\Media::find(old('image_id', $banner->image_id))?->toPickerArray() : null;
        $mediaConfig = [
            'media' => $image ? [$image] : [], 'multiple' => false,
            'uploadUrl' => route('admin.media.store'), 'libraryUrl' => route('admin.media.index', ['format' => 'json']),
            'messages' => ['type' => __('admin.media.errors.type'), 'size' => __('admin.media.errors.size'), 'failed' => __('admin.media.upload_failed')],
        ];
    @endphp

    <x-admin.page-header :title="$banner->exists ? $banner->title : __('admin.banners.add')" :back="route('admin.banners.index')" />

    <form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" class="space-y-8">
        @csrf
        @if ($banner->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.banners.content_title')" :description="__('admin.banners.content_text')">
            <x-admin.select name="placement" :label="__('admin.banners.fields.placement')" :value="$banner->placement"
                :options="collect(\App\Models\Banner::placements())->mapWithKeys(fn ($p) => [$p => \App\Models\Banner::placementLabel($p)])->all()" :help="__('admin.banners.placement_help')" />
            <x-admin.translatable-input name="title" :label="__('admin.banners.fields.title')" :values="$banner->getTranslations('title')" required maxlength="120" />
            <x-admin.translatable-input name="text" :label="__('admin.banners.fields.text')" :values="$banner->getTranslations('text')" multiline :rows="2" maxlength="300" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.translatable-input name="button_label" :label="__('admin.banners.fields.button_label')" :values="$banner->getTranslations('button_label')" maxlength="40" />
                <x-admin.input name="url" :label="__('admin.banners.fields.url')" :value="$banner->url" maxlength="500" dir="ltr" placeholder="/collections/sale" :help="__('admin.banners.url_help')" />
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.banners.image_title')" :description="__('admin.banners.image_text')">
            <div x-data="mediaField(@js($mediaConfig))" class="space-y-4">
                <input type="hidden" name="image_id" :value="media[0]?.id ?? ''">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex h-28 w-44 items-center justify-center overflow-hidden rounded-lg border border-dashed border-line-strong bg-canvas">
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
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.banners.schedule_title')" :description="__('admin.banners.schedule_text')">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-admin.input name="starts_at" type="datetime-local" :label="__('admin.discounts.fields.starts_at')" :value="$local($banner->starts_at)" />
                <x-admin.input name="ends_at" type="datetime-local" :label="__('admin.discounts.fields.ends_at')" :value="$local($banner->ends_at)" />
                <x-admin.input name="sort_order" type="number" min="0" :label="__('admin.common.sort_order')" :value="$banner->sort_order" />
            </div>
            <x-admin.toggle name="is_active" :label="__('admin.banners.fields.active')" :checked="$banner->is_active ?? true" />
        </x-admin.form-section>

        @include('admin.settings._save-bar', ['always' => ! $banner->exists])
    </form>

    @if ($banner->exists)
        <div class="mt-6"><x-admin.confirm-delete :action="route('admin.banners.destroy', $banner)" :label="__('admin.banners.delete')" :title="__('admin.banners.delete_title')" :message="__('admin.banners.delete_text')" /></div>
    @endif
@endsection
