@extends('storefront.layouts.app')

@section('title', __('store.addresses.title'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-4xl px-4 pb-20 pt-10 sm:px-6">
        <x-store.breadcrumbs :items="[['label' => __('store.account.title'), 'url' => store_route('account.dashboard')], ['label' => __('store.addresses.title')]]" />
        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-3xl font-semibold tracking-tight">{{ __('store.addresses.title') }}</h1>
            @if ($addresses->count() < \App\Models\CustomerAddress::MAX_PER_CUSTOMER)
                <x-store.button :href="store_route('account.addresses.create')"><x-icon name="plus" class="size-4" /> {{ __('store.addresses.add') }}</x-store.button>
            @endif
        </div>

        @if (session('status'))<x-store.alert class="mt-6">{{ session('status') }}</x-store.alert>@endif

        @if ($addresses->isEmpty())
            <div class="mt-8 rounded-md border border-dashed border-line px-6 py-14 text-center">
                <x-icon name="map-pin" class="mx-auto size-8 text-stone" />
                <p class="mt-4 font-medium">{{ __('store.addresses.empty_title') }}</p>
                <p class="mt-1 text-[15px] text-ink-soft">{{ __('store.addresses.empty_text') }}</p>
            </div>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($addresses as $address)
                    <li @class(['flex flex-col rounded-2xl border p-5', 'border-ink' => $address->is_default, 'border-line' => ! $address->is_default])>
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold">{{ $address->label ?: $address->name }}</p>
                            @if ($address->is_default)<span class="rounded-full bg-ink px-2.5 py-0.5 text-xs font-medium text-paper">{{ __('store.addresses.default') }}</span>@endif
                        </div>
                        <address class="mt-2 flex-1 text-[15px] not-italic leading-relaxed text-ink-soft">
                            @if ($address->label){{ $address->name }}<br>@endif
                            {{ $address->address_line }}@if ($address->building), {{ $address->building }}@endif<br>
                            {{ $address->regionLine() }}<br>
                            <span dir="ltr">{{ $address->phone }}</span>
                        </address>
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                            <a href="{{ store_route('account.addresses.edit', ['address' => $address->id]) }}" class="underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.addresses.edit') }}</a>
                            @unless ($address->is_default)
                                <form method="POST" action="{{ store_route('account.addresses.default', ['address' => $address->id]) }}">@csrf<button class="underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ __('store.addresses.make_default') }}</button></form>
                            @endunless
                            <form method="POST" action="{{ store_route('account.addresses.destroy', ['address' => $address->id]) }}" x-data @submit="if (! confirm(@js(__('store.addresses.delete_confirm')))) $event.preventDefault()" class="ms-auto">
                                @csrf @method('DELETE')
                                <button class="text-danger underline decoration-danger/30 underline-offset-4">{{ __('store.addresses.delete') }}</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
