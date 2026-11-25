@extends('admin.settings.layout')

@section('title', __('admin.currencies.title'))

@section('settings')
    <x-admin.page-header :title="__('admin.currencies.title')" :subtitle="__('admin.currencies.subtitle')" :back="route('admin.settings.index')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.settings.currencies.create')" icon="plus">{{ __('admin.currencies.add') }}</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.alert class="mb-6">
        {{ __('admin.currencies.base_notice', ['code' => $base->code]) }}
    </x-admin.alert>

    <x-admin.card :padding="false">
        @if ($currencies->isEmpty())
            <x-admin.empty-state icon="coins" :title="__('admin.currencies.empty_title')" :text="__('admin.currencies.empty_text')">
                <x-admin.button :href="route('admin.settings.currencies.create')" icon="plus">{{ __('admin.currencies.add') }}</x-admin.button>
            </x-admin.empty-state>
        @else
            {{-- Table on tablets and up --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-medium text-muted">
                            <th scope="col" class="px-5 py-3 text-start font-medium">{{ __('admin.currencies.currency') }}</th>
                            <th scope="col" class="px-5 py-3 text-start font-medium">{{ __('admin.currencies.symbol') }}</th>
                            <th scope="col" class="px-5 py-3 text-start font-medium">{{ __('admin.currencies.rate') }}</th>
                            <th scope="col" class="px-5 py-3 text-start font-medium">{{ __('admin.common.status') }}</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">{{ __('admin.common.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($currencies as $currency)
                            <tr class="transition hover:bg-canvas/50">
                                <td class="px-5 py-3.5">
                                    {{-- Switch it on or off, or make it the currency prices are kept in. --}}
                                    @unless ($currency->is_base)
                                        <form method="POST" action="{{ route('admin.settings.currencies.toggle', $currency) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[13px] font-medium text-ink-soft hover:text-ink">{{ $currency->is_active ? __('admin.currencies.deactivate') : __('admin.currencies.activate') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.settings.currencies.make-base', $currency) }}" class="inline"
                                              x-data @submit="if (! confirm(@js(__('admin.currencies.make_base_confirm')))) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="text-[13px] font-medium text-ink-soft hover:text-ink">{{ __('admin.currencies.make_base') }}</button>
                                        </form>
                                    @endunless
                                    <a href="{{ route('admin.settings.currencies.edit', $currency) }}" class="flex items-center gap-3">
                                        <span class="inline-flex h-7 min-w-12 items-center justify-center rounded-md bg-canvas px-2 text-xs font-semibold tracking-wide text-ink-soft">{{ $currency->code }}</span>
                                        <span class="font-medium text-ink hover:underline">{{ $currency->name }}</span>
                                        @if ($currency->is_base)
                                            <x-admin.badge tone="brand">{{ __('admin.currencies.base') }}</x-admin.badge>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft">
                                    {{ $currency->symbol }}@if ($currency->symbol_ar) <span class="text-muted">/</span> <span lang="ar">{{ $currency->symbol_ar }}</span>@endif
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft" dir="ltr">
                                    @if ($currency->is_base)
                                        <span class="text-muted">—</span>
                                    @else
                                        1 {{ $base->code }} = {{ rtrim(rtrim((string) $currency->exchange_rate, '0'), '.') }} {{ $currency->code }}
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-admin.badge :tone="$currency->is_active ? 'success' : 'neutral'">
                                        {{ $currency->is_active ? __('admin.common.active') : __('admin.common.inactive') }}
                                    </x-admin.badge>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.settings.currencies.edit', $currency) }}" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-ink/5 hover:text-ink" aria-label="{{ __('admin.common.edit') }} {{ $currency->code }}">
                                            <x-icon name="pencil" class="size-4" />
                                        </a>
                                        @unless ($currency->is_base)
                                            <x-admin.confirm-delete icon-only
                                                :action="route('admin.settings.currencies.destroy', $currency)"
                                                :title="__('admin.currencies.delete_title', ['code' => $currency->code])"
                                                :message="__('admin.currencies.delete_text')"
                                                :label="__('admin.common.delete').' '.$currency->code" />
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cards on phones --}}
            <ul class="divide-y divide-line md:hidden">
                @foreach ($currencies as $currency)
                    <li class="flex items-center gap-3 p-4">
                        <a href="{{ route('admin.settings.currencies.edit', $currency) }}" class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold">{{ $currency->code }}</span>
                                <span class="truncate text-sm text-ink-soft">{{ $currency->name }}</span>
                            </span>
                            <span class="mt-1 flex flex-wrap items-center gap-2 text-[13px] text-muted">
                                @if ($currency->is_base)
                                    <x-admin.badge tone="brand">{{ __('admin.currencies.base') }}</x-admin.badge>
                                @else
                                    <span dir="ltr">1 {{ $base->code }} = {{ rtrim(rtrim((string) $currency->exchange_rate, '0'), '.') }}</span>
                                @endif
                                <x-admin.badge :tone="$currency->is_active ? 'success' : 'neutral'">
                                    {{ $currency->is_active ? __('admin.common.active') : __('admin.common.inactive') }}
                                </x-admin.badge>
                            </span>
                        </a>
                        <x-icon name="chevron-right" class="size-4 text-muted rtl:rotate-180" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
@endsection
