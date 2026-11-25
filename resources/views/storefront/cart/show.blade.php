@extends('storefront.layouts.app')

@section('title', __('store.cart.title'))
@section('noindex', true)

@php
    $taxLabel = settings()->bool('tax.enabled') && settings()->bool('tax.prices_include_tax') ? settings()->trans('tax.label') : null;
@endphp

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-10 sm:px-6 lg:px-10">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.cart.title') }}</h1>

        @foreach ($summary->notices as $notice)
            <x-store.alert class="mt-6" tone="warning">{{ $notice }}</x-store.alert>
        @endforeach
        @error('quantity')<x-store.alert class="mt-6" tone="danger">{{ $message }}</x-store.alert>@enderror
        @foreach ($errors->get('cart') as $message)<x-store.alert class="mt-6" tone="warning">{{ $message }}</x-store.alert>@endforeach

        @if ($summary->isEmpty())
            <div class="mt-10 rounded-md border border-dashed border-line px-6 py-16 text-center">
                <x-icon name="shopping-bag" class="mx-auto size-8 text-stone" />
                <p class="mt-4 text-lg font-medium">{{ __('store.cart.empty_title') }}</p>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.cart.empty_text') }}</p>
                <x-store.button :href="store_route('collections.show', ['slug' => 'new-in'])" class="mt-6">{{ __('store.home.shop_new') }}</x-store.button>
            </div>
        @else
            <div class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14">
                <section class="lg:col-span-8" aria-label="{{ __('store.cart.items') }}">
                    <ul class="divide-y divide-line border-y border-line">
                        @foreach ($summary->lines as $line)
                            @php
                                $image = $line->variant->image ?? $line->product->media->first();
                                $url = store_route('products.show', ['slug' => $line->product->slug]).($line->variant->optionValues->isNotEmpty() ? '?variant='.$line->variant->id : '');
                                $max = min($line->available ?? \App\Services\Cart\CartService::MAX_LINE_QUANTITY, \App\Services\Cart\CartService::MAX_LINE_QUANTITY);
                            @endphp
                            <li class="flex gap-4 py-6 sm:gap-6">
                                <a href="{{ $url }}" class="w-24 shrink-0 sm:w-32">
                                    @if ($image)
                                        <img src="{{ $image->url('thumb') }}" alt="" loading="lazy" class="aspect-[4/5] w-full rounded-sm bg-wool-soft object-cover">
                                    @else
                                        <span class="flex aspect-[4/5] w-full items-center justify-center rounded-sm bg-wool-soft"><x-icon name="image" class="size-6 text-stone/50" /></span>
                                    @endif
                                </a>

                                <div class="flex min-w-0 flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h2 class="text-[15px] font-medium leading-snug"><a href="{{ $url }}" class="hover:underline hover:underline-offset-4">{{ $line->product->name }}</a></h2>
                                            @if ($title = $line->variant->title())
                                                <p class="mt-1 text-sm text-ink-soft">{{ $title }}</p>
                                            @endif
                                            @if ($line->item->optionsLabel())
                                                <p class="mt-1 text-sm text-ink-soft">{{ $line->item->optionsLabel() }}</p>
                                            @endif
                                            <p class="mt-1 text-sm text-ink-soft">
                                                {{ price($line->unitPrice) }}
                                                @if ($line->variant->isOnSale())<s class="ms-1 text-stone">{{ price($line->variant->compare_at_price) }}</s>@endif
                                            </p>
                                        </div>
                                        <p class="shrink-0 text-[15px] font-medium">{{ price($line->lineTotal) }}</p>
                                    </div>

                                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                                        <form method="POST" action="{{ store_route('cart.update', ['item' => $line->item->id]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex h-10 items-center rounded-md border border-line">
                                                <button name="quantity" value="{{ $line->item->quantity - 1 }}" class="flex h-full w-10 items-center justify-center text-ink-soft hover:text-ink" aria-label="{{ __('store.product.decrease') }}"><x-icon name="minus" class="size-4" /></button>
                                                <label for="qty-{{ $line->item->id }}" class="sr-only">{{ __('store.product.quantity') }}</label>
                                                {{-- No name until typed in, so the +/− buttons' own values are what gets sent. --}}
                                                <input id="qty-{{ $line->item->id }}" type="number" value="{{ $line->item->quantity }}" min="0" max="{{ $max }}" inputmode="numeric"
                                                       x-data @change="$el.name = 'quantity'; $el.form.requestSubmit()"
                                                       @keydown.enter.prevent="$el.name = 'quantity'; $el.form.requestSubmit()"
                                                       class="h-full w-12 border-0 bg-transparent text-center text-[15px] [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" dir="ltr">
                                                <button name="quantity" value="{{ $line->item->quantity + 1 }}" @disabled($line->item->quantity >= $max) class="flex h-full w-10 items-center justify-center text-ink-soft hover:text-ink disabled:opacity-30" aria-label="{{ __('store.product.increase') }}"><x-icon name="plus" class="size-4" /></button>
                                            </div>
                                        </form>

                                        @if ($line->available !== null && $line->available <= 5)
                                            <p class="text-sm text-brand-red">{{ trans_choice('store.product.only_left_choice', $line->available, ['count' => $line->available]) }}</p>
                                        @endif

                                        <form method="POST" action="{{ store_route('cart.remove', ['item' => $line->item->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-ink-soft underline decoration-ink/30 underline-offset-4 hover:text-ink">{{ __('store.cart.remove') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ store_route('shop') }}" class="mt-6 inline-flex items-center gap-2 text-[15px] text-ink-soft hover:text-ink">
                        <x-icon name="arrow-left" class="size-4 rtl:rotate-180" /> {{ __('store.cart.continue') }}
                    </a>
                </section>

                <aside class="lg:col-span-4" aria-labelledby="summary-heading">
                    <div class="rounded-md bg-wool-soft p-6 lg:sticky lg:top-28">
                        <h2 id="summary-heading" class="text-lg font-semibold">{{ __('store.cart.summary') }}</h2>
                        <dl class="mt-5 space-y-3 text-[15px]">
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-soft">{{ trans_choice('store.cart.subtotal', $summary->count, ['count' => $summary->count]) }}</dt>
                                <dd class="font-medium">{{ price($summary->subtotal) }}</dd>
                            </div>
                            @foreach ($summary->promotions as $deal)
                                <div class="flex justify-between gap-4 text-brand-red">
                                    <dt>{{ __('store.deals.line', ['name' => $deal['name'][app()->getLocale()] ?? $deal['name']['en']]) }}</dt>
                                    <dd>−{{ price($deal['amount']) }}</dd>
                                </div>
                            @endforeach
                            @if ($summary->discount)
                                <div class="flex justify-between gap-4 text-brand-red">
                                    <dt>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</dt>
                                    <dd>{{ $summary->freeShipping() ? __('store.discount.free_delivery') : '−'.price($summary->discountAmount) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-soft">{{ __('store.cart.shipping') }}</dt>
                                <dd class="text-ink-soft">{{ __('store.cart.shipping_at_checkout') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-5 flex justify-between gap-4 border-t border-line pt-5 text-lg font-semibold">
                            <span>{{ __('store.cart.total') }}</span>
                            <span>{{ price($summary->discountedSubtotal()) }}</span>
                        </div>
                        @if ($taxLabel)
                            <p class="mt-1 text-sm text-stone">{{ __('store.product.tax_included', ['tax' => $taxLabel]) }}</p>
                        @endif
                        @if (($current = app(\App\Services\Currency\CurrencyService::class)->current())->code !== config('store.base_currency'))
                            <p class="mt-3 text-sm text-stone">{{ __('store.cart.currency_note', ['currency' => $current->code, 'base' => config('store.base_currency')]) }}</p>
                        @endif

                        <div class="mt-5">@include('storefront.partials.discount-form')</div>
                        @include('storefront.partials.discount-forms-outer')

                        <x-store.button :href="store_route('checkout')" data-tour="store-checkout" class="mt-6 w-full">{{ __('store.cart.checkout') }}</x-store.button>
                        <p class="mt-2 text-center text-sm text-stone">{{ __('store.cart.shipping_note') }}</p>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
