@extends('storefront.layouts.app')

@section('title', __('store.returns.title', ['number' => $order->number]))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-3xl px-4 pb-20 pt-10 sm:px-6">
        <x-store.breadcrumbs :items="[['label' => __('store.order.title', ['number' => $order->number]), 'url' => store_route('orders.show', ['token' => $order->access_token])], ['label' => __('store.returns.heading')]]" />
        <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ __('store.returns.heading') }}</h1>
        <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.returns.intro', ['days' => $days]) }}</p>

        @if ($errors->any())<x-store.alert tone="error" class="mt-6">{{ $errors->first() }}</x-store.alert>@endif

        <form method="POST" action="{{ store_route('returns.store', ['token' => $order->access_token]) }}" class="mt-8 space-y-8">
            @csrf
            <fieldset>
                <legend class="text-lg font-semibold">{{ __('store.returns.which') }}</legend>
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($order->items as $item)
                        @php $max = $returnable[$item->id] ?? 0; @endphp
                        <li class="flex items-center gap-4 py-4">
                            @if ($item->image)<img src="{{ $item->image }}" alt="" class="aspect-[4/5] w-14 rounded-sm bg-wool-soft object-cover">@endif
                            <span class="min-w-0 flex-1 text-[15px]">
                                <span class="block">{{ $item->name }}</span>
                                <span class="block text-sm text-ink-soft">{{ $item->variant_title }}@if ($item->variant_title) · @endif{{ __('store.order.qty', ['count' => $item->quantity]) }}</span>
                            </span>
                            @if ($max > 0)
                                <label class="sr-only" for="item-{{ $item->id }}">{{ __('store.returns.quantity_for', ['name' => $item->name]) }}</label>
                                <select id="item-{{ $item->id }}" name="items[{{ $item->id }}]" class="rounded-md border border-line bg-white py-2 ps-3 pe-8 text-[15px]">
                                    @for ($q = 0; $q <= $max; $q++)
                                        <option value="{{ $q }}" @selected((int) old("items.{$item->id}") === $q)>{{ $q }}</option>
                                    @endfor
                                </select>
                            @else
                                <span class="text-sm text-stone">{{ __('store.returns.already_requested') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </fieldset>

            <fieldset>
                <legend class="text-lg font-semibold">{{ __('store.returns.resolution') }}</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach (['refund', 'exchange'] as $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-line p-4 has-[:checked]:border-ink has-[:checked]:bg-wool-soft/60">
                            <input type="radio" name="resolution" value="{{ $option }}" @checked(old('resolution', 'refund') === $option) class="mt-1 accent-ink">
                            <span><span class="block font-medium">{{ __("store.returns.resolutions.{$option}") }}</span><span class="block text-sm text-ink-soft">{{ __("store.returns.resolutions_help.{$option}") }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label for="reason" class="block text-sm font-medium">{{ __('store.returns.reason') }}</label>
                    <select id="reason" name="reason" required class="store-field">
                        @foreach (\App\Models\ReturnRequest::REASONS as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ __("store.returns.reasons.{$reason}") }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <label for="note" class="block text-sm font-medium">{{ __('store.returns.note') }} <span class="font-normal text-stone">({{ __('store.common.optional') }})</span></label>
                    <textarea id="note" name="note" rows="3" maxlength="1000" class="store-field" placeholder="{{ __('store.returns.note_placeholder') }}">{{ old('note') }}</textarea>
                </div>
            </div>

            <x-store.button>{{ __('store.returns.submit') }}</x-store.button>
        </form>
    </div>
@endsection
