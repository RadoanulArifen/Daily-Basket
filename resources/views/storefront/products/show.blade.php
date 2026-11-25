@extends('storefront.layouts.app')

@php
    $images = $product->media;
    $cover = $images->first();
    $taxLabel = settings()->bool('tax.enabled') && settings()->bool('tax.prices_include_tax') ? settings()->trans('tax.label') : null;
    $descriptionHtml = \App\Support\RichText::render($product->description);
    $metaDescription = $product->seo_description ?: \Illuminate\Support\Str::limit(\App\Support\RichText::text($product->short_description ?: $product->description), 160);
@endphp

@section('title', $product->seo_title ?: $product->name)
@if (filled($metaDescription))
    @section('meta_description', $metaDescription)
@endif
@section('og_type', 'product')
@section('canonical', store_route('products.show', ['slug' => $product->slug]))

@push('meta')
    @if ($cover)
        <meta property="og:image" content="{{ $cover->url('large') }}">
        <meta property="og:image:width" content="{{ $cover->width }}">
        <meta property="og:image:height" content="{{ $cover->height }}">
    @endif
    <meta property="product:price:amount" content="{{ $selected->price->toDecimal() }}">
    <meta property="product:price:currency" content="{{ $selected->price->currency }}">
    @php
        $ld = app(\App\Services\Seo\StructuredData::class);
        $ldCrumbs = $category ? [['label' => $category->name, 'url' => store_route('collections.show', ['slug' => $category->slug])], ['label' => $product->name]] : [['label' => $product->name]];
    @endphp
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode($ld->product($product, $category)) !!}</script>
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode($ld->breadcrumbs($ldCrumbs)) !!}</script>
@endpush

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-6 sm:px-6 lg:px-10" x-data="productPage(@js($config))">
        @php
            $crumbs = [];
            if ($category) {
                $crumbs[] = ['label' => $category->name, 'url' => store_route('collections.show', ['slug' => $category->slug])];
            }
            $crumbs[] = ['label' => $product->name];
        @endphp
        <x-store.breadcrumbs :items="$crumbs" class="max-sm:hidden" />

        <div class="mt-4 grid gap-8 sm:mt-8 lg:grid-cols-12 lg:gap-14">
            {{-- Gallery --}}
            <section class="lg:col-span-7" aria-label="{{ __('store.product.images') }}">
                @if ($images->isEmpty())
                    <div class="flex aspect-[4/5] items-center justify-center rounded-sm bg-wool-soft"><x-icon name="image" class="size-10 text-stone/50" /></div>
                @else
                    <div class="lg:grid lg:grid-cols-[4.5rem_minmax(0,1fr)] lg:gap-4">
                        {{-- Main image: swipeable strip on phones, single image with thumbnails on desktop --}}
                        <div class="relative lg:order-2">
                            <ul class="-mx-4 flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] sm:mx-0 lg:block lg:overflow-visible"
                                @scroll.debounce.100ms="image = Math.round(Math.abs($el.scrollLeft) / $el.clientWidth)"
                                x-effect="if (window.innerWidth < 1024 && Math.round(Math.abs($el.scrollLeft) / $el.clientWidth) !== image) $el.scrollTo({ left: image * $el.clientWidth * (document.dir === 'rtl' ? -1 : 1), behavior: 'smooth' })">
                                @foreach ($images as $i => $media)
                                    <li @class(['w-full shrink-0 snap-center lg:w-auto', 'lg:hidden' => $i > 0]) x-bind:class="{ 'lg:hidden': image !== {{ $i }} }">
                                        <img src="{{ $media->url('large') }}" srcset="{{ $media->srcset() }}" sizes="(min-width: 1024px) 50vw, 100vw"
                                             alt="{{ $media->getTranslation('alt', app()->getLocale()) ?: $product->name }}"
                                             width="{{ $media->width }}" height="{{ $media->height }}"
                                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}" @if ($i === 0) fetchpriority="high" @endif decoding="async"
                                             class="aspect-[4/5] w-full bg-wool-soft object-cover sm:rounded-sm">
                                    </li>
                                @endforeach
                            </ul>
                            @if ($images->count() > 1)
                                <div class="mt-3 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                                    @foreach ($images as $i => $media)
                                        <span class="size-1.5 rounded-full transition" :class="image === {{ $i }} ? 'bg-ink' : 'bg-line'"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($images->count() > 1)
                            <ul class="hidden space-y-3 lg:order-1 lg:block">
                                @foreach ($images as $i => $media)
                                    <li>
                                        <button type="button" @click="image = {{ $i }}" class="block w-full overflow-hidden rounded-sm border-2 transition"
                                                :class="image === {{ $i }} ? 'border-ink' : 'border-transparent hover:border-line'"
                                                :aria-current="image === {{ $i }}" aria-label="{{ __('store.product.show_image', ['n' => $i + 1]) }}">
                                            <img src="{{ $media->url('thumb') }}" alt="" loading="lazy" class="aspect-[4/5] w-full bg-wool-soft object-cover">
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Details --}}
            <section class="lg:col-span-5 lg:pt-2" aria-labelledby="product-title">
                <h1 id="product-title" class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $product->name }}</h1>
                @if ($product->rating_count)
                    <a href="#reviews" class="mt-2 inline-flex items-center gap-2 text-sm text-ink-soft hover:text-ink">
                        <x-store.stars :rating="$product->rating_average" />
                        {{ trans_choice('store.reviews.count', $product->rating_count, ['count' => $product->rating_count]) }}
                    </a>
                @endif

                {{-- Price: server-rendered for the default variant, then kept in sync by Alpine --}}
                <div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1" aria-live="polite">
                    <span class="text-2xl font-medium" :class="variant?.compare && 'text-brand-red'" x-text="variant?.price" @class(['text-brand-red' => $selected->isOnSale()])>{{ price($selected->price) }}</span>
                    <s class="text-lg text-stone" x-show="variant?.compare" x-text="variant?.compare" @unless ($selected->isOnSale()) x-cloak @endunless>{{ $selected->isOnSale() ? price($selected->compare_at_price) : '' }}</s>
                    <span class="rounded-sm bg-brand-red/10 px-2 py-0.5 text-sm font-medium text-brand-red" x-show="variant?.save" x-text="@js(__('store.product.save_percent')).replace(':percent', variant?.save)" x-cloak></span>
                </div>
                @if ($taxLabel)
                    <p class="mt-1 text-sm text-stone">{{ __('store.product.tax_included', ['tax' => $taxLabel]) }}</p>
                @endif
                @if ($deal = app(\App\Services\Promotions\PromotionService::class)->forProduct($product))
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-brand-red/10 px-3 py-1.5 text-sm font-medium text-brand-red">
                        <x-icon name="tag" class="size-4" /> {{ $deal->label() }}
                    </p>
                @endif

                @if ($product->short_description)
                    <div class="store-prose mt-6 text-[17px] leading-relaxed text-ink-soft">{!! \App\Support\RichText::render($product->short_description) !!}</div>
                @endif

                <form method="POST" action="{{ store_route('cart.add') }}" data-tour="store-add" x-data="addToCart" @submit.prevent="submit($event)" data-error="{{ __('store.cart.error') }}" class="mt-8 space-y-6">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $selected->id }}" :value="variant?.id">

                    {{-- Options --}}
                    @foreach ($product->options as $option)
                        <fieldset>
                            <legend class="text-[15px]">
                                <span class="font-medium">{{ $option->name }}:</span>
                                <span class="text-ink-soft" x-text="chosenLabel(options[{{ $loop->index }}])">{{ $selected->optionValues->firstWhere('product_option_id', $option->id)?->value }}</span>
                            </legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($option->values as $value)
                                    <button type="button" @click="choose({{ $option->id }}, {{ $value->id }})"
                                            :aria-pressed="isChosen({{ $option->id }}, {{ $value->id }})"
                                            class="min-h-11 min-w-11 rounded-md border px-4 text-[15px] transition"
                                            :class="[isChosen({{ $option->id }}, {{ $value->id }}) ? 'border-ink bg-ink text-paper' : 'border-line hover:border-ink', ! isAvailable({{ $option->id }}, {{ $value->id }}) && ! isChosen({{ $option->id }}, {{ $value->id }}) ? 'text-stone line-through decoration-stone/60' : '']">
                                        {{ $value->value }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    {{-- Stock message --}}
                    <p class="flex items-center gap-2 text-[15px]" aria-live="polite">
                        <template x-if="variant && ! variant.available"><span class="text-danger">{{ __('store.product.sold_out_variant') }}</span></template>
                        <template x-if="variant && variant.available && variant.left !== null && variant.left > 0">
                            <span class="text-brand-red" x-text="@js(__('store.product.only_left')).replace(':count', variant.left)"></span>
                        </template>
                        <template x-if="variant && variant.available && variant.left === null">
                            <span class="inline-flex items-center gap-2 text-success"><x-icon name="check" class="size-4" /> {{ __('store.product.in_stock') }}</span>
                        </template>
                        <template x-if="! variant"><span class="text-stone">{{ __('store.product.unavailable_combo') }}</span></template>
                    </p>

                    <div class="flex gap-3">
                        <div class="flex h-12 items-center rounded-md border border-line">
                            <button type="button" @click="step(-1)" class="flex h-full w-11 items-center justify-center text-ink-soft hover:text-ink" aria-label="{{ __('store.product.decrease') }}"><x-icon name="minus" class="size-4" /></button>
                            <label for="quantity" class="sr-only">{{ __('store.product.quantity') }}</label>
                            <input id="quantity" type="number" name="quantity" min="1" max="99" value="1" x-model.number="quantity" inputmode="numeric"
                                   class="h-full w-12 border-0 bg-transparent text-center text-[15px] [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" dir="ltr">
                            <button type="button" @click="step(1)" class="flex h-full w-11 items-center justify-center text-ink-soft hover:text-ink" aria-label="{{ __('store.product.increase') }}"><x-icon name="plus" class="size-4" /></button>
                        </div>
                        <x-store.button class="flex-1" x-bind:disabled="busy || ! variant || ! variant.available" :disabled="! $selected->isInStock()">
                            <span x-show="! busy">
                                <span x-text="variant && ! variant.available ? @js(__('store.product.sold_out')) : @js(__('store.product.add_to_bag'))">{{ $selected->isInStock() ? __('store.product.add_to_bag') : __('store.product.sold_out') }}</span>
                            </span>
                            <span x-show="busy" x-cloak>{{ __('store.product.adding') }}</span>
                        </x-store.button>
                    </div>

                    <p x-show="error" x-text="error" x-cloak class="text-[15px] text-danger" role="alert"></p>
                    @error('quantity')<p class="text-[15px] text-danger" role="alert">{{ $message }}</p>@enderror
                    @error('variant_id')<p class="text-[15px] text-danger" role="alert">{{ $message }}</p>@enderror
                </form>

                <div class="mt-3">
                    <x-store.wishlist-button :product="$product" label />
                </div>

                <ul class="mt-8 space-y-3 border-t border-line pt-6 text-[15px] text-ink-soft">
                    <li class="flex gap-3"><x-icon name="truck" class="mt-0.5 size-5 text-brand-red" /> {{ __('store.home.promise_delivery_text') }}</li>
                    <li class="flex gap-3"><x-icon name="rotate-ccw" class="mt-0.5 size-5 text-brand-red" /> {{ __('store.home.promise_returns_text') }}</li>
                </ul>

                @if (filled((string) $descriptionHtml))
                    <div class="mt-8 border-t border-line pt-6">
                        <h2 class="text-[15px] font-medium">{{ __('store.product.description') }}</h2>
                        <div class="store-prose mt-3 text-[15px] leading-relaxed text-ink-soft">{!! $descriptionHtml !!}</div>
                    </div>
                @endif

                <p class="mt-6 text-sm text-stone" x-show="variant?.sku" x-cloak>{{ __('store.product.sku') }}: <span x-text="variant?.sku" dir="ltr"></span></p>
            </section>
        </div>

        @include('storefront.products._reviews')

        @if ($related->isNotEmpty())
            <section class="mt-20 border-t border-line pt-12" aria-labelledby="related-heading">
                <h2 id="related-heading" class="text-2xl font-semibold tracking-tight">{{ __('store.product.related') }}</h2>
                <x-store.product-grid :products="$related" class="mt-8" />
            </section>
        @endif
    </div>
@endsection
