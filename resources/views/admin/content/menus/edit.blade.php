@extends('admin.layouts.app')

@php $menuName = __("admin.menus.handles.{$menu->handle}") !== "admin.menus.handles.{$menu->handle}" ? __("admin.menus.handles.{$menu->handle}") : $menu->name; @endphp

@section('title', $menuName)

@section('content')
    <x-admin.page-header :title="$menuName" :back="route('admin.menus.index')" :subtitle="__('admin.menus.edit_text')" />

    @php
        $parentOptions = ['' => __('admin.menus.no_parent')] + $topLevel->mapWithKeys(fn ($i) => [$i->id => $i->label])->all();
        $row = function ($item, $isChild) use ($menu, $parentOptions) {
            return view('admin.content.menus._item', compact('item', 'isChild', 'menu', 'parentOptions'))->render();
        };
    @endphp

    <x-admin.card :padding="false">
        @if ($topLevel->isEmpty())
            <x-admin.empty-state icon="navigation" :title="__('admin.menus.empty_title')" :text="__('admin.menus.empty_text')" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($topLevel as $item)
                    {!! $row($item, false) !!}
                    @foreach ($children->get($item->id, []) as $child)
                        {!! $row($child, true) !!}
                    @endforeach
                @endforeach
            </ul>
        @endif
    </x-admin.card>

    <x-admin.card :title="__('admin.menus.add_item')" class="mt-6">
        <form method="POST" action="{{ route('admin.menus.items.store', $menu) }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.translatable-input name="label" :label="__('admin.menus.fields.label')" :values="[]" required maxlength="60" />
                <x-admin.input name="url" :label="__('admin.menus.fields.url')" dir="ltr" placeholder="/collections/gifts" required :help="__('admin.menus.url_help')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.select name="parent_id" :label="__('admin.menus.fields.parent')" :options="$parentOptions" />
                <x-admin.input name="sort_order" type="number" min="0" :label="__('admin.common.sort_order')" :value="($topLevel->max('sort_order') ?? 0) + 1" />
            </div>
            <input type="hidden" name="is_active" value="1">
            <x-admin.button icon="plus">{{ __('admin.menus.add_item') }}</x-admin.button>
        </form>
    </x-admin.card>
@endsection
