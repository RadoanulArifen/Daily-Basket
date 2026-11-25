{{-- One logo uploader. $field is "logo" (dark) or "logo_white". --}}
@php
    $current = $values[$field] ?? null;
    $url = $current ? \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($current) : null;
    $onDark = $field === 'logo_white';
@endphp
<div x-data="{ preview: @js($url), remove: false }" class="space-y-3">
    <p class="text-[13px] font-medium">{{ $label }}</p>
    <div class="flex flex-wrap items-center gap-4">
        <div @class([
            'flex h-20 w-40 items-center justify-center overflow-hidden rounded-lg border border-dashed border-line-strong',
            'bg-canvas' => ! $onDark,
            'bg-ink-strong' => $onDark,   // a white logo needs a dark backdrop to be visible
        ])>
            <template x-if="preview && ! remove">
                <img :src="preview" alt="{{ $label }}" class="max-h-16 max-w-36 object-contain">
            </template>
            <template x-if="! preview || remove">
                <x-icon name="image" @class(['size-6', 'text-muted' => ! $onDark, 'text-white/50' => $onDark]) />
            </template>
        </div>
        <div class="space-y-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-line-strong bg-surface px-3 py-1.5 text-sm font-medium shadow-xs hover:bg-canvas">
                <x-icon name="image" class="size-4" /> {{ __('admin.settings.general.logo_choose') }}
                <input type="file" name="{{ $field }}" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only"
                       @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); remove = false }">
            </label>
            @if ($current)
                <label class="flex items-center gap-2 text-sm text-ink-soft">
                    <input type="checkbox" name="remove_{{ $field }}" value="1" x-model="remove" class="size-4 accent-danger-600">
                    {{ __('admin.settings.general.logo_remove') }}
                </label>
            @endif
            <p class="text-[13px] text-muted">{{ $help }}</p>
        </div>
    </div>
    <x-admin.field-error :name="$field" />
</div>
