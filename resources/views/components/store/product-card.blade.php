{{-- Product tile used on listings, search, wishlist and the home page. Needs ->media and ->variants loaded. --}}
@props(['product', 'eager' => false])

@php
    $url = store_route('products.show', ['slug' => $product->slug]);
    $images = $product->media->take(2);
    $variants = $product->variants->where('is_active', true);
    $soldOut = $variants->every(fn ($v) => ! $v->isInStock());
    $onSale = $variants->contains(fn ($v) => $v->isOnSale());
@endphp

<article {{ $attributes->class('group relative') }}>
    <div class="relative aspect-[4/5] overflow-hidden rounded-sm bg-wool-soft">
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if ($first = $images->first())
                <img src="{{ $first->url('medium') }}" srcset="{{ $first->srcset() }}" sizes="(min-width: 1024px) 22vw, (min-width: 640px) 33vw, 50vw"
                     alt="" width="{{ $first->width }}" height="{{ $first->height }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
                     class="size-full object-cover transition duration-500 group-hover:scale-[1.02]">
                @if ($second = $images->get(1))
                    <img src="{{ $second->url('medium') }}" alt="" loading="lazy" decoding="async"
                         class="absolute inset-0 size-full object-cover opacity-0 transition duration-500 group-hover:opacity-100 max-lg:hidden">
                @endif
            @else
                <span class="flex size-full items-center justify-center"><x-icon name="image" class="size-8 text-stone/50" /></span>
            @endif
        </a>

        <div class="absolute start-2.5 top-2.5 flex flex-col items-start gap-1.5">
            @if ($soldOut)
                <span class="rounded-sm bg-ink px-2 py-1 text-xs font-medium text-paper">{{ __('store.product.sold_out') }}</span>
            @elseif ($onSale)
                <span class="rounded-sm bg-brand-red px-2 py-1 text-xs font-medium text-white">{{ __('store.product.sale') }}</span>
            @endif
        </div>

        <x-store.wishlist-button :product="$product" class="absolute end-2.5 top-2.5" />
    </div>

    <div class="mt-3 space-y-1">
        <h3 class="text-[15px] leading-snug">
            <a href="{{ $url }}" class="hover:underline hover:underline-offset-4">{{ $product->name }}</a>
        </h3>
        <x-store.price :product="$product" />
        @if ($product->rating_count)
            <p class="flex items-center gap-1.5 text-sm text-stone"><x-store.stars :rating="$product->rating_average" size="size-3.5" /> ({{ $product->rating_count }})</p>
        @endif
    </div>
</article>
