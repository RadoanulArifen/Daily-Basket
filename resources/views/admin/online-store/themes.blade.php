@extends('admin.layouts.app')

@section('title', __('admin.online.themes'))

@section('content')
    @php
        $me = auth('admin')->user();
        $published = \App\Models\Setting::query()->where('group', 'theme')->max('updated_at');
        // The design this store is running, and a picture of it.
        $activeTheme = config('store.themes.'.config('store.theme'), []);
        $themeName = $activeTheme['name'] ?? ucfirst((string) config('store.theme'));
        $themePreview = ($p = $activeTheme['preview'] ?? null) && file_exists(public_path($p))
            ? asset($p)
            : asset('images/demo/scene-hero.webp');
    @endphp
    <x-admin.page-header :title="__('admin.online.themes')">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.online-store.preview.start')" target="_blank" icon="external-link">{{ __('admin.online.view_store') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="grid md:grid-cols-[18rem_1fr]">
            <div class="border-b border-line bg-subdued p-4 md:border-e md:border-b-0">
                <img src="{{ $themePreview }}" alt="" class="aspect-[4/3] w-full rounded-lg border border-line object-cover shadow-card">
            </div>
            <div class="flex flex-col p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold text-ink-strong">{{ $themeName }}</h2>
                    <x-admin.badge tone="success" dot="full">{{ __('admin.online.current') }}</x-admin.badge>
                    @if ($theme->hasDraft())<x-admin.badge tone="attention">{{ __('admin.online.unpublished_changes') }}</x-admin.badge>@endif
                </div>
                <p class="mt-1 text-[13px] text-muted">{{ $published ? __('admin.online.last_published', ['date' => \Illuminate\Support\Carbon::parse($published)->timezone(config('store.display_timezone'))->translatedFormat('j M Y, H:i')]) : __('admin.online.never_customised') }}</p>
                <p class="mt-3 text-[13px] text-ink-soft">{{ __('admin.online.theme_text') }}</p>
                <div class="mt-auto flex flex-wrap gap-2 pt-4">
                    <x-admin.button :href="route('admin.online-store.customize')">{{ __('admin.online.customize') }}</x-admin.button>
                    @if ($me->mayAccess('settings'))<x-admin.button variant="secondary" :href="route('admin.online-store.code')" icon="square-pen">{{ __('admin.online.code_title') }}</x-admin.button>@endif
                </div>
            </div>
        </div>
    </x-admin.card>

    <x-admin.card :title="__('admin.online.files_title')" class="mt-4">
        <p class="text-[13px] text-ink-soft">{{ __('admin.online.files_text') }}</p>
    </x-admin.card>
@endsection
