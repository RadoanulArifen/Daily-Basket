{{--
    Contextual save bar. Include inside a form whose x-data has `dirty`.
    Hidden until something changes (always shown when $always is true, e.g. "new" pages);
    it then covers the top bar with Discard / Save, and leaving the page asks first.
--}}
@php $always = $always ?? false; @endphp

<div x-data="{ leaving: false }"
     x-init="$el.closest('form')?.addEventListener('submit', () => leaving = true)"
     @beforeunload.window="if ((dirty || {{ $always ? 'true' : 'false' }}) && ! leaving) { $event.preventDefault(); $event.returnValue = ''; }">

    {{-- Top bar --}}
    <div x-show="dirty || {{ $always ? 'true' : 'false' }}" x-cloak x-transition.opacity.duration.150ms
         class="fixed inset-x-0 top-0 z-50 flex h-14 items-center gap-3 bg-topbar px-3 text-white sm:px-4" role="region" aria-label="{{ __('admin.common.unsaved') }}">
        <span class="hidden w-56 shrink-0 lg:block"></span>
        <p class="flex flex-1 items-center gap-2 text-sm font-semibold">
            <x-icon name="circle-alert" class="size-4 text-white/70" />
            {{ __('admin.common.unsaved') }}
        </p>
        <button type="button" @click="if (confirm(@js(__('admin.common.discard_confirm')))) { leaving = true; window.location.reload(); }"
                class="h-8 rounded-lg px-3 text-[13px] font-semibold text-white ring-1 ring-white/25 hover:bg-white/10">{{ __('admin.common.discard') }}</button>
        <button type="submit" class="h-8 rounded-lg bg-surface px-3 text-[13px] font-semibold text-ink-strong hover:bg-[#e3e3e3]">{{ $label ?? __('admin.common.save') }}</button>
    </div>

    {{-- Bottom save --}}
    <div class="mt-6 flex justify-end border-t border-line pt-4">
        <x-admin.button x-bind:disabled="! dirty && ! {{ $always ? 'true' : 'false' }}">{{ $label ?? __('admin.common.save') }}</x-admin.button>
    </div>
</div>
