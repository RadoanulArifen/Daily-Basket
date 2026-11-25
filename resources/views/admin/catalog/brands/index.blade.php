@extends('admin.layouts.app')

@section('fullWidth', true)

@section('title', __('admin.brands.title'))

@section('content')
    <x-admin.page-header :title="__('admin.brands.title')" :subtitle="__('admin.brands.subtitle')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.brands.create')" icon="plus">{{ __('admin.brands.add') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false" class="mt-6">
        @if ($brands->isEmpty())
            <x-admin.empty-state icon="tag" :title="__('admin.brands.empty_title')" :text="__('admin.brands.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($brands as $brand)
                    <li>
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-subdued sm:px-5">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-lg border border-line bg-surface p-1.5">
                                @if ($logo = $brand->logoUrl())
                                    <img src="{{ $logo }}" alt="" class="max-h-full max-w-full object-contain">
                                @else
                                    <x-icon name="tag" class="size-4 text-muted" />
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[14px] font-medium">{{ $brand->name }}</span>
                                <span class="block text-[13px] text-muted">{{ $brand->origin ?: $brand->slug }}</span>
                            </span>
                            <span class="text-[13px] text-muted">{{ trans_choice('admin.brands.products', $brand->products_count, ['count' => $brand->products_count]) }}</span>
                            @unless ($brand->is_active)<x-admin.badge>{{ __('admin.common.hidden') }}</x-admin.badge>@endunless
                            <x-icon name="chevron-right" class="size-4 text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3">{{ $brands->links() }}</div>
        @endif
    </x-admin.card>
@endsection
