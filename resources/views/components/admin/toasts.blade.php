{{-- Flash messages (session 'success' / 'warning' / 'error') plus window.toast('…') from JavaScript. --}}
@php
    $initial = collect(['success', 'warning', 'error'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
        ->values();

    if ($errors->any() && ! session()->has('error')) {
        $initial->push(['type' => 'error', 'message' => __('admin.common.fix_errors')]);
    }
@endphp

<div
    x-data="{
        items: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.items.push({ id, ...toast });
            setTimeout(() => this.remove(id), toast.type === 'success' ? 4000 : 7000);
        },
        remove(id) { this.items = this.items.filter(t => t.id !== id) },
    }"
    x-init="@js($initial).forEach(t => add(t))"
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in items" :key="toast.id">
        <div x-transition.opacity.duration.200ms
             class="pointer-events-auto flex max-w-md items-center gap-3 rounded-xl px-4 py-3 text-sm text-white shadow-lg"
             :class="toast.type === 'error' ? 'bg-danger-700' : 'bg-ink'">
            <span x-text="toast.message"></span>
            <button type="button" @click="remove(toast.id)" class="-me-1 rounded p-0.5 text-white/70 hover:text-white" aria-label="{{ __('admin.common.close') }}">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>

{{-- The same messages, printed by the server: they show even if scripts are blocked, and are
     swapped for the animated toasts above as soon as Alpine starts. --}}
@if ($initial->isNotEmpty())
    <div x-data x-init="$el.remove()" class="pointer-events-none fixed inset-x-0 bottom-4 z-[59] flex flex-col items-center gap-2 px-4" role="status">
        @foreach ($initial as $toast)
            <div class="pointer-events-auto max-w-md rounded-xl px-4 py-3 text-sm text-white shadow-lg {{ $toast['type'] === 'error' ? 'bg-danger-700' : 'bg-ink' }}">{{ $toast['message'] }}</div>
        @endforeach
    </div>
@endif
