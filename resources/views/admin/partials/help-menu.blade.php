{{-- The ? menu in the top bar: every guided tour this admin may take, its status, and start / continue / restart. --}}
@if ($tourOverview['total'] > 0)
    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
        <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="true" aria-label="{{ __('tours.menu_label') }}" title="{{ __('tours.menu_label') }}"
                class="relative inline-flex size-9 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white">
            <x-icon name="circle-help" class="size-5" />
            @if ($tourOverview['active'])
                <span class="absolute end-1.5 top-1.5 size-2 rounded-full bg-[#4cc26e]" aria-hidden="true"></span>
            @endif
        </button>

        <div x-show="open" x-cloak x-transition.origin.top @click.outside="open = false"
             class="absolute end-0 mt-2 w-[min(25rem,calc(100vw-1rem))] overflow-hidden rounded-xl bg-surface text-ink shadow-popover">
            <div class="px-4 pt-4 pb-3">
                <p class="text-[15px] font-semibold text-ink-strong">{{ __('tours.title') }}</p>
                <p class="mt-0.5 text-[13px] text-muted">{{ __('tours.intro') }} {{ __('tours.done_count', ['done' => $tourOverview['done'], 'total' => $tourOverview['total']]) }}</p>
                <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-[#ebebeb]" aria-hidden="true">
                    <div class="h-full rounded-full bg-[#2E9E4F]" style="width: {{ $tourOverview['total'] ? round($tourOverview['done'] / $tourOverview['total'] * 100) : 0 }}%"></div>
                </div>
            </div>

            <ul class="divide-y divide-line border-t border-line">
                @foreach ($tourOverview['tours'] as $tour)
                    <li @class(['flex items-center gap-3 px-4 py-3', 'bg-[#f7fbf8]' => $tour['status'] === 'started'])>
                        @if ($tour['status'] === 'done')
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#2E9E4F] text-white"><x-icon name="check" class="size-4" /></span>
                        @elseif ($tour['status'] === 'started')
                            <span class="size-7 shrink-0 rounded-full border-[3px] border-[#d4d4d4] border-t-[#2E9E4F] border-s-[#2E9E4F]" aria-hidden="true"></span>
                        @else
                            <span class="size-7 shrink-0 rounded-full border-2 border-[#d4d4d4]" aria-hidden="true"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-semibold text-ink-strong">{{ $tour['name'] }}</p>
                            <p class="text-xs text-muted">
                                @if (! $tour['available'])
                                    {{ __('tours.status.needs_order') }}
                                @elseif ($tour['status'] === 'done')
                                    {{ __('tours.status.done') }} · {{ trans_choice('tours.steps_count', $tour['steps'], ['count' => $tour['steps']]) }}
                                @elseif ($tour['status'] === 'started')
                                    {{ __('tours.status.started', ['step' => $tour['reached'], 'total' => $tour['steps']]) }}
                                @else
                                    {{ trans_choice('tours.steps_count', $tour['steps'], ['count' => $tour['steps']]) }} · {{ __('tours.minutes', ['count' => $tour['minutes']]) }}
                                @endif
                            </p>
                        </div>
                        @if ($tour['available'])
                            <form method="POST" action="{{ route('admin.tours.update') }}">
                                @csrf
                                <input type="hidden" name="tour" value="{{ $tour['key'] }}">
                                @if ($tour['status'] === 'started')
                                    <x-admin.button type="submit" size="sm" name="action" value="resume">{{ __('tours.actions.continue') }}</x-admin.button>
                                @else
                                    <x-admin.button type="submit" size="sm" variant="secondary" name="action" value="start">{{ $tour['status'] === 'done' ? __('tours.actions.restart') : __('tours.actions.start') }}</x-admin.button>
                                @endif
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="flex items-center justify-between gap-3 border-t border-line px-4 py-2.5 text-[13px]">
                <form method="POST" action="{{ route('admin.tours.update') }}">@csrf<button type="submit" name="action" value="welcome_show" class="underline hover:text-ink-strong">{{ __('tours.actions.show_welcome') }}</button></form>
                <form method="POST" action="{{ route('admin.tours.update') }}" x-data @submit="if (! confirm(@js(__('tours.actions.reset_confirm')))) $event.preventDefault()">@csrf<button type="submit" name="action" value="reset" class="text-muted underline hover:text-ink-strong">{{ __('tours.actions.reset') }}</button></form>
            </div>
        </div>
    </div>
@endif
