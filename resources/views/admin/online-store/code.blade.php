@extends('admin.layouts.app')

@section('title', __('admin.online.code_title'))

@section('content')
    <x-admin.page-header :title="__('admin.online.code_title')" :back="route('admin.online-store.themes')" :subtitle="__('admin.online.code_subtitle')" />

    <x-admin.alert tone="warning" class="mb-4">{{ __('admin.online.code_warning') }}</x-admin.alert>
    @if ($errors->any())<x-admin.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-admin.alert>@endif

    <form method="POST" action="{{ route('admin.online-store.code.save') }}" class="space-y-4" x-data="{ dirty: false }" @input="dirty = true">
        @csrf @method('PUT')
        @foreach ([['css', 14, '/* .store-button { border-radius: 999px; } */'], ['head', 8, '<!-- Meta Pixel, Google Tag Manager, verification tags… -->'], ['body', 6, '<!-- Chat widget… -->']] as [$field, $rows, $placeholder])
            <x-admin.card :title="__('admin.online.code.'.$field)" :description="__('admin.online.code_help.'.$field)">
                <label for="code-{{ $field }}" class="sr-only">{{ __('admin.online.code.'.$field) }}</label>
                <textarea id="code-{{ $field }}" name="{{ $field }}" rows="{{ $rows }}" spellcheck="false" dir="ltr" placeholder="{{ $placeholder }}"
                          x-data @keydown.tab.prevent="const s = $el.selectionStart; $el.setRangeText('    ', s, $el.selectionEnd, 'end')"
                          class="admin-field bg-[#1f2124]! font-mono text-[12.5px] leading-5 text-[#e6e6e6]! placeholder:text-[#8a8a8a]">{{ old($field, $code[$field]) }}</textarea>
            </x-admin.card>
        @endforeach

        <x-admin.card :title="__('admin.online.code.domains')" :description="__('admin.online.code_help.domains')">
            <label for="code-domains" class="sr-only">{{ __('admin.online.code.domains') }}</label>
            <textarea id="code-domains" name="domains" rows="3" dir="ltr" spellcheck="false" placeholder="connect.facebook.net&#10;www.googletagmanager.com" class="admin-field font-mono text-[12.5px]">{{ old('domains', implode("\n", array_map(fn ($d) => preg_replace('#^https://#', '', $d), $code['domains']))) }}</textarea>
        </x-admin.card>

        @include('admin.settings._save-bar')
    </form>

    <x-admin.card :title="__('admin.online.versions')" :padding="false" class="mt-6">
        @if ($versions->isEmpty())
            <p class="px-4 pb-4 pt-2 text-[13px] text-muted">{{ __('admin.online.no_versions') }}</p>
        @else
            <ul class="mt-3 divide-y divide-line border-t border-line">
                @foreach ($versions as $version)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-2.5 text-[13px]">
                        <span class="font-medium">{{ $version->created_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y, H:i') }}</span>
                        <span class="text-muted">{{ $version->admin?->name ?? '—' }} · {{ __('admin.online.version_size', ['css' => mb_strlen((string) $version->css), 'code' => mb_strlen((string) $version->head) + mb_strlen((string) $version->body)]) }}</span>
                        @if ($loop->first)<x-admin.badge tone="success">{{ __('admin.online.current') }}</x-admin.badge>@else
                            <form method="POST" action="{{ route('admin.online-store.code.restore', $version) }}" class="ms-auto" x-data @submit="if (! confirm(@js(__('admin.online.restore_confirm')))) $event.preventDefault()">@csrf<x-admin.button variant="secondary" size="sm" icon="undo-2">{{ __('admin.online.restore') }}</x-admin.button></form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
