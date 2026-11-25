@extends('admin.layouts.app')

@section('title', $brand->exists ? $brand->name : __('admin.brands.add'))

@section('content')
    <x-admin.page-header :title="$brand->exists ? $brand->name : __('admin.brands.add')" :back="route('admin.brands.index')" />

    <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @if ($brand->exists) @method('PUT') @endif

        <x-admin.form-section :title="__('admin.brands.details')">
            <x-admin.translatable-input name="name" :label="__('admin.brands.name')" :values="(array) $brand->getTranslations('name')" required maxlength="120" />
            <x-admin.input name="slug" :label="__('admin.common.slug')" :value="old('slug', $brand->slug)" :help="__('admin.brands.slug_help')" dir="ltr" />
            <x-admin.translatable-input name="origin" :label="__('admin.brands.origin')" :values="(array) $brand->getTranslations('origin')" maxlength="120" :help="__('admin.brands.origin_help')" />
            <x-admin.translatable-input name="description" :label="__('admin.brands.description')" :values="(array) $brand->getTranslations('description')" rich :rows="3" />
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.brands.logos')" :description="__('admin.brands.logos_help')">
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([['logo', __('admin.brands.logo')], ['logo_small', __('admin.brands.logo_small')]] as [$field, $label])
                    <div>
                        <label for="{{ $field }}" class="mb-1 block text-[13px] font-medium">{{ $label }}</label>
                        @if ($current = $brand->logoUrl($field === 'logo_small'))
                            <div class="mb-2 flex items-center gap-3">
                                <span class="flex h-14 items-center rounded-lg border border-line bg-surface px-3"><img src="{{ $current }}" alt="" class="max-h-10 max-w-32 object-contain"></span>
                                <label class="flex items-center gap-2 text-[13px] text-muted">
                                    <input type="checkbox" name="remove_{{ $field }}" value="1" class="size-4 accent-brand-600">{{ __('admin.brands.remove_logo') }}
                                </label>
                            </div>
                        @endif
                        <input id="{{ $field }}" type="file" name="{{ $field }}" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="admin-field py-1.5 text-[13px]">
                        @error($field)<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>

        <x-admin.form-section :title="__('admin.common.visibility')">
            <x-admin.toggle name="is_active" :label="__('admin.brands.active')" :checked="old('is_active', $brand->is_active)" />
            <x-admin.input name="position" type="number" :label="__('admin.brands.position')" :value="old('position', $brand->position)" :help="__('admin.brands.position_help')" min="0" max="999" dir="ltr" />
        </x-admin.form-section>

        <div class="flex flex-wrap items-center gap-3">
            <x-admin.button>{{ __('admin.common.save') }}</x-admin.button>
            @if ($brand->exists)
                <x-admin.button variant="secondary" :href="store_route('brands.show', ['slug' => $brand->slug])" target="_blank">{{ __('admin.brands.view') }}</x-admin.button>
                <button type="submit" form="delete-brand" class="ms-auto text-[13px] text-danger-600 underline">{{ __('admin.common.delete') }}</button>
            @endif
        </div>
    </form>

    @if ($brand->exists)
        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" id="delete-brand" x-data
              @submit="if (! confirm(@js(__('admin.brands.delete_confirm')))) $event.preventDefault()">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
