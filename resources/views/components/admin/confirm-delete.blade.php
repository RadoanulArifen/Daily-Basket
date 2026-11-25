{{--
    A delete button that asks first.
    <x-admin.confirm-delete :action="route('...destroy', $item)" :title="..." :message="..." />
--}}
@props([
    'action',
    'title',
    'message' => null,
    'confirm' => null,
    'label' => null,
    'iconOnly' => false,
])

<div x-data="{ open: false }" class="inline-flex">
    @if ($iconOnly)
        <button type="button" @click="open = true" class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-danger-50 hover:text-danger-600" aria-label="{{ $label ?? __('admin.common.delete') }}">
            <x-icon name="trash-2" class="size-4" />
        </button>
    @else
        <x-admin.button type="button" variant="secondary" icon="trash-2" x-on:click="open = true" class="text-danger-600">
            {{ $label ?? __('admin.common.delete') }}
        </x-admin.button>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 p-4 sm:items-center"
             @keydown.escape.window="open = false" role="dialog" aria-modal="true">
            <div x-show="open" x-transition @click.outside="open = false" x-trap.noscroll="open"
                 class="w-full max-w-md rounded-2xl bg-surface p-5 shadow-xl sm:p-6">
                <div class="flex gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-danger-50 text-danger-600">
                        <x-icon name="triangle-alert" class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                        @if ($message)<p class="mt-1 text-sm text-muted">{{ $message }}</p>@endif
                    </div>
                </div>
                <form method="POST" action="{{ $action }}" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @csrf
                    @method('DELETE')
                    <x-admin.button type="button" variant="secondary" x-on:click="open = false">{{ __('admin.common.cancel') }}</x-admin.button>
                    <x-admin.button variant="danger">{{ $confirm ?? __('admin.common.delete') }}</x-admin.button>
                </form>
            </div>
        </div>
    </template>
</div>
