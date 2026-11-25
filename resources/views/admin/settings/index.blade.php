@extends('admin.settings.layout')

@section('title', __('admin.nav.settings'))

@section('settings')
    <x-admin.page-header :title="__('admin.nav.settings')" :subtitle="__('admin.settings.subtitle')" />

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($sections as $section)
            @php $tag = $section['url'] ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($section['url']) href="{{ $section['url'] }}" @endif
                @class([
                    'group flex items-start gap-4 rounded-xl border border-line bg-surface p-4 shadow-xs transition',
                    'hover:border-line-strong hover:shadow-sm' => $section['url'],
                ])>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <x-icon :name="$section['icon']" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-ink">{{ $section['title'] }}</span>
                        @if (! $section['url'] && empty($section['badge']))
                            <x-admin.badge>{{ __('admin.common.soon') }}</x-admin.badge>
                        @endif
                    </span>
                    <span class="mt-1 block text-[13px] leading-relaxed text-muted">{{ $section['text'] }}</span>
                    @if (! empty($section['badge']))
                        <span class="mt-2 block text-xs font-medium text-ink-soft">{{ $section['badge'] }}</span>
                    @endif
                </span>
                @if ($section['url'])
                    <x-icon name="chevron-right" class="mt-2.5 size-4 text-muted group-hover:text-ink rtl:rotate-180" />
                @endif
            </{{ $tag }}>
        @endforeach
    </div>
@endsection
