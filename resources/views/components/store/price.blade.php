{{-- Price of a product (lowest active variant), with the compare-at price when on sale. Needs ->variants loaded. --}}
@props(['product', 'size' => 'base'])

@php
    $variants = $product->variants->where('is_active', true);
    $cheapest = $variants->sortBy(fn ($v) => $v->price->minor)->first();
    $varies = $variants->map(fn ($v) => $v->price->minor)->unique()->count() > 1;
@endphp

@if ($cheapest)
    <p {{ $attributes->class(['flex flex-wrap items-baseline gap-x-2', 'text-[15px]' => $size === 'base', 'text-2xl' => $size === 'lg']) }}>
        <span @class(['font-medium', 'text-brand-red' => $cheapest->isOnSale()])>
            @if ($varies)<span class="font-normal text-ink-soft">{{ __('store.product.from') }}</span>@endif
            {{ price($cheapest->price) }}
        </span>
        @if ($cheapest->isOnSale())
            <s class="text-stone {{ $size === 'lg' ? 'text-lg' : 'text-sm' }}"><span class="sr-only">{{ __('store.product.was') }}</span>{{ price($cheapest->compare_at_price) }}</s>
        @endif
    </p>
@endif
