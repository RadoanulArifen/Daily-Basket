{{-- One menu item with its inline edit form. --}}
<li x-data="{ open: false }" class="{{ $isChild ? 'bg-canvas/30' : '' }}">
    <div class="flex items-center gap-3 py-3 pe-4 sm:pe-5" style="padding-inline-start: {{ $isChild ? '2.75rem' : '1.25rem' }}">
        @if ($isChild)<span class="h-4 w-3 shrink-0 border-b border-s border-line-strong" aria-hidden="true"></span>@endif
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium">{{ $item->label }}</span>
            <span class="block truncate text-[13px] text-muted" dir="ltr">{{ $item->url }}</span>
        </span>
        @unless ($item->is_active)<x-admin.badge>{{ __('admin.common.hidden') }}</x-admin.badge>@endunless
        <x-admin.button type="button" variant="ghost" size="sm" x-on:click="open = ! open">{{ __('admin.common.edit') }}</x-admin.button>
        <x-admin.confirm-delete icon-only :action="route('admin.menus.items.destroy', [$menu, $item])" :title="__('admin.menus.delete_title', ['name' => $item->label])" :message="$isChild ? __('admin.menus.delete_text') : __('admin.menus.delete_parent_text')" />
    </div>
    <form x-show="open" x-cloak x-collapse method="POST" action="{{ route('admin.menus.items.update', [$menu, $item]) }}" class="space-y-4 border-t border-line bg-canvas/50 p-4 sm:px-5">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.translatable-input name="label" :label="__('admin.menus.fields.label')" :values="$item->getTranslations('label')" required maxlength="60" :id="'label-'.$item->id" />
            <x-admin.input name="url" :label="__('admin.menus.fields.url')" :value="$item->url" dir="ltr" required :id="'url-'.$item->id" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.select name="parent_id" :label="__('admin.menus.fields.parent')" :options="collect($parentOptions)->except($item->id)->all()" :value="$item->parent_id" :id="'parent-'.$item->id" />
            <x-admin.input name="sort_order" type="number" min="0" :label="__('admin.common.sort_order')" :value="$item->sort_order" :id="'sort-'.$item->id" />
        </div>
        <div class="flex flex-wrap gap-6">
            <x-admin.toggle name="is_active" :label="__('admin.menus.fields.active')" :checked="$item->is_active" :id="'active-'.$item->id" />
            <x-admin.toggle name="open_in_new_tab" :label="__('admin.menus.fields.new_tab')" :checked="$item->open_in_new_tab" :id="'tab-'.$item->id" />
        </div>
        <x-admin.button>{{ __('admin.common.save') }}</x-admin.button>
    </form>
</li>
