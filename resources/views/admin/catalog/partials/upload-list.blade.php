{{-- Upload progress and errors, inside an x-data using mediaMixin. --}}
<ul x-show="uploads.length" class="space-y-2" aria-live="polite">
    <template x-for="upload in uploads" :key="upload.id">
        <li class="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm" :class="upload.error ? 'border-danger-600/30 bg-danger-50' : 'border-line bg-canvas'">
            <x-icon name="image" class="size-4 text-muted" />
            <span class="min-w-0 flex-1">
                <span class="block truncate" x-text="upload.name"></span>
                <span x-show="upload.error" class="block text-[13px] text-danger-600" x-text="upload.error"></span>
                <span x-show="! upload.error" class="mt-1 block h-1 overflow-hidden rounded-full bg-line">
                    <span class="block h-full bg-brand-600 transition-all" :style="`width: ${upload.progress}%`"></span>
                </span>
            </span>
            <button type="button" x-show="upload.error" @click="dismissUpload(upload.id)" class="rounded p-1 text-muted hover:text-ink" aria-label="{{ __('admin.common.close') }}">
                <x-icon name="x" class="size-4" />
            </button>
        </li>
    </template>
</ul>
