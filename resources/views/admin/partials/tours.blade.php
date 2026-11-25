{{--
    Guided tours (App\Services\Tours\AdminTours): the data block the popups read
    (resources/js/admin/tours.js) and the welcome screen shown on an admin's first sign-in.
    Expects $tourOverview from the admin layout.
--}}
@php
    $tourPayload = [
        'endpoint' => route('admin.tours.update'),
        'csrf' => csrf_token(),
        'route' => request()->route()?->getName(),
        'active' => $tourOverview['active'],
        'labels' => [
            'next' => __('tours.popup.next'),
            'back' => __('tours.popup.back'),
            'finish' => __('tours.popup.finish'),
            'skip' => __('tours.popup.skip'),
            'close' => __('tours.popup.close'),
            'stepOf' => __('tours.popup.step_of', ['step' => ':step', 'total' => ':total']),
            'notHere' => __('tours.popup.not_here'),
            'resume' => __('tours.popup.resume'),
            'nextUp' => __('tours.next_up', ['name' => ':name']),
        ],
    ];
    $firstTour = collect($tourOverview['tours'])->firstWhere('available', true)['key'] ?? null;
@endphp
<script type="application/json" id="admin-tour-data">@json($tourPayload)</script>

@if ($tourOverview['welcome'] && $firstTour)
    <div x-data="{ open: true, tour: @js($firstTour), never: false }" x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/55 p-3 sm:p-6" @keydown.escape.window="open = false">
        <section role="dialog" aria-modal="true" aria-labelledby="tour-welcome-title" class="max-h-full w-full max-w-[42.5rem] overflow-y-auto rounded-2xl bg-surface shadow-popover">
            <div class="space-y-2 px-6 pt-6 sm:px-8 sm:pt-7">
                <span class="inline-flex rounded-full bg-[#e8f5e9] px-2.5 py-1 text-xs font-semibold text-[#1e7a3c]">{{ __('tours.welcome.badge') }}</span>
                <h2 id="tour-welcome-title" class="text-2xl font-bold text-ink-strong">{{ __('tours.welcome.title') }}</h2>
                <p class="text-sm leading-relaxed text-muted">{{ __('tours.welcome.text') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.tours.update') }}">
                @csrf
                <fieldset class="grid gap-3 px-6 pt-5 sm:grid-cols-2 sm:px-8">
                    <legend class="sr-only">{{ __('tours.title') }}</legend>
                    @foreach ($tourOverview['tours'] as $tour)
                        <label @class(['tour-choice flex cursor-pointer flex-col gap-1 rounded-xl border p-4 transition', 'pointer-events-none opacity-50' => ! $tour['available']])
                               :class="tour === @js($tour['key']) ? 'border-[#2E9E4F] bg-[#f4fbf5] ring-1 ring-[#2E9E4F]' : 'border-line hover:border-[#bdbdbd]'">
                            <input type="radio" name="tour" value="{{ $tour['key'] }}" x-model="tour" class="sr-only" @disabled(! $tour['available'])>
                            <span class="text-[15px] font-semibold text-ink-strong">{{ $tour['name'] }}</span>
                            <span class="text-[13px] leading-snug text-muted">{{ $tour['summary'] }}</span>
                            <span class="mt-1.5 text-xs text-muted">
                                @if ($tour['available'])
                                    {{ trans_choice('tours.steps_count', $tour['steps'], ['count' => $tour['steps']]) }} · {{ __('tours.minutes', ['count' => $tour['minutes']]) }}
                                @else
                                    {{ __('tours.status.needs_order') }}
                                @endif
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 sm:px-8 sm:py-6">
                    <label class="flex items-center gap-2 text-[13px] text-muted">
                        <input type="checkbox" x-model="never" class="admin-checkbox"> {{ __('tours.welcome.never') }}
                    </label>
                    <div class="flex gap-2">
                        <x-admin.button type="submit" variant="secondary" name="action" x-bind:value="never ? 'welcome_never' : 'welcome_later'" value="welcome_later">{{ __('tours.welcome.later') }}</x-admin.button>
                        <x-admin.button type="submit" name="action" value="start">{{ __('tours.welcome.start') }}</x-admin.button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endif
