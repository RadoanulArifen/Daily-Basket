@extends('admin.layouts.app')

@section('title', __('admin.online.customize'))
@section('fullWidth', true)

@section('content')
    <x-admin.page-header :title="__('admin.online.customize')" :back="route('admin.online-store.themes')" :subtitle="__('admin.online.theme_name')">
        <x-slot:badges>
            @if ($hasDraft)<x-admin.badge tone="attention">{{ __('admin.online.unpublished_changes') }}</x-admin.badge>@endif
        </x-slot:badges>
        <x-slot:actions>
            @if ($hasDraft)
                <form method="POST" action="{{ route('admin.online-store.customize.discard') }}" x-data @submit="if (! confirm(@js(__('admin.online.discard_confirm')))) $event.preventDefault()">@csrf @method('DELETE')<x-admin.button variant="ghost">{{ __('admin.online.discard') }}</x-admin.button></form>
            @endif
            <x-admin.button variant="secondary" type="submit" form="theme-form">{{ __('admin.online.save_draft') }}</x-admin.button>
            <form method="POST" action="{{ route('admin.online-store.customize.publish') }}" data-tour="customize-publish">@csrf<x-admin.button :disabled="! $hasDraft">{{ __('admin.online.publish') }}</x-admin.button></form>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

    <div class="grid gap-4 xl:grid-cols-[22rem_minmax(0,1fr)]" x-data="{ device: 'desktop' }">
        <form id="theme-form" method="POST" action="{{ route('admin.online-store.customize.save') }}" class="min-w-0 space-y-4"
              x-data="{ sections: @js($sections) }">
            @csrf @method('PUT')

            <x-admin.card :title="__('admin.online.colours')">
                <div class="space-y-3">
                    @foreach (['color_primary', 'color_accent', 'color_background', 'color_surface'] as $key)
                        <div class="flex items-center gap-3" x-data="{ v: @js($values[$key]) }">
                            <input type="color" x-model="v" class="size-9 shrink-0 cursor-pointer rounded-lg border border-line bg-surface p-0.5" aria-label="{{ __('admin.online.fields.'.$key) }}">
                            <label class="min-w-0 flex-1 text-[13px]">
                                <span class="block font-medium">{{ __('admin.online.fields.'.$key) }}</span>
                                <span class="block text-xs text-muted">{{ __('admin.online.help.'.$key) }}</span>
                            </label>
                            <input name="{{ $key }}" x-model="v" maxlength="7" pattern="#[0-9a-fA-F]{6}" class="admin-field w-24 font-mono text-xs" dir="ltr" aria-label="{{ __('admin.online.fields.'.$key) }} (hex)">
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.online.typography')">
                <label for="font" class="sr-only">{{ __('admin.online.font') }}</label>
                <select id="font" name="font" class="admin-field">
                    @foreach (array_keys(\App\Services\Theme\ThemeSettings::FONTS) as $font)
                        <option value="{{ $font }}" @selected($values['font'] === $font)>{{ __('admin.online.fonts.'.$font) }}</option>
                    @endforeach
                </select>
            </x-admin.card>

            <x-admin.card :title="__('admin.online.wording')">
                <x-admin.translatable-input name="trust_badges" :label="__('admin.online.trust_badges')" multiline :rows="3" maxlength="300"
                    :values="(array) settings()->get('theme.trust_badges', [])" :help="__('admin.online.trust_badges_help')" />
                <x-admin.translatable-input name="footer_categories_heading" :label="__('admin.online.footer_categories')" maxlength="40"
                    :values="(array) settings()->get('theme.footer_categories_heading', [])" :help="__('admin.online.footer_categories_help')" class="mt-4" />
            </x-admin.card>

            <x-admin.card :title="__('admin.online.announcement')">
                <div class="space-y-3">
                    <x-admin.toggle name="announcement_enabled" :label="__('admin.settings.general.announcement_show')" :checked="$general['announcement_enabled'] ?? false" />
                    <x-admin.translatable-input name="announcement_text" :label="__('admin.settings.general.announcement_message')" :values="(array) ($general['announcement_text'] ?? [])" maxlength="160" :help="__('admin.online.announcement_help')" />
                </div>
            </x-admin.card>

            <x-admin.card data-tour="customize-sections" :title="__('admin.online.home_sections')" :description="__('admin.online.home_sections_help')" :padding="false">
                <ul class="mt-3 divide-y divide-line border-t border-line">
                    <template x-for="(s, i) in sections" :key="s.key">
                        <li class="flex items-center gap-2 px-4 py-2" :class="s.visible ? '' : 'opacity-50'">
                            <input type="hidden" :name="`sections[${i}][key]`" :value="s.key">
                            <input type="hidden" :name="`sections[${i}][visible]`" :value="s.visible ? 1 : 0">
                            <label class="flex flex-1 items-center gap-2 text-[13px]">
                                <input type="checkbox" x-model="s.visible" class="admin-checkbox">
                                <span x-text="@js(\App\Services\Theme\ThemeSettings::labels())[s.key]"></span>
                            </label>
                            <button type="button" @click="if (i > 0) { const t = sections[i - 1]; sections[i - 1] = s; sections[i] = t; }" :disabled="i === 0" class="rounded p-1 text-muted hover:bg-subdued disabled:opacity-30" aria-label="{{ __('admin.online.move_up') }}"><x-icon name="chevron-up" class="size-4" /></button>
                            <button type="button" @click="if (i < sections.length - 1) { const t = sections[i + 1]; sections[i + 1] = s; sections[i] = t; }" :disabled="i === sections.length - 1" class="rounded p-1 text-muted hover:bg-subdued disabled:opacity-30" aria-label="{{ __('admin.online.move_down') }}"><x-icon name="chevron-down" class="size-4" /></button>
                        </li>
                    </template>
                </ul>
            </x-admin.card>

            <p class="px-1 text-xs text-muted">{{ __('admin.online.draft_help') }}</p>
        </form>

        {{-- Live preview of the real store with the draft applied --}}
        <div class="min-w-0 xl:sticky xl:top-20 xl:self-start">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-[13px] font-medium text-muted">{{ __('admin.online.preview') }}</p>
                <div class="inline-flex rounded-lg bg-surface p-0.5 shadow-card">
                    @foreach (['desktop' => 'monitor', 'mobile' => 'smartphone'] as $mode => $icon)
                        <button type="button" @click="device = @js($mode)" :class="device === @js($mode) ? 'bg-[#e3e3e3]' : ''" class="rounded-md p-1.5" aria-label="{{ __('admin.online.devices.'.$mode) }}"><x-icon :name="$icon" class="size-4" /></button>
                    @endforeach
                </div>
            </div>
            <div class="overflow-hidden rounded-xl bg-[#d4d4d4] shadow-card" data-tour="customize-preview">
                <iframe src="{{ route('admin.online-store.preview.start') }}" title="{{ __('admin.online.preview') }}" loading="lazy"
                        class="mx-auto block h-[75vh] bg-surface transition-all" :class="device === 'mobile' ? 'w-[390px]' : 'w-full'"></iframe>
            </div>
        </div>
    </div>
@endsection
