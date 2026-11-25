{{-- Furnivo product card — markup and styles from the design (site.zip). --}}
@props(['product'])
@php
    $url = store_route('products.show', ['slug' => $product->slug]);
    $image = $product->media->first();
    $brand = $product->relationLoaded('brandRecord') ? $product->brandRecord : null;
    $variant = $product->variants->sortBy(fn ($v) => $v->price->minor)->first();
@endphp
<div class="fv-card" style="display: flex; flex-direction: column; gap: 15px; background: transparent; position: relative;">
    <div style="position: relative; width: 100%; aspect-ratio: 1 / 1; overflow: hidden; background: rgb(239, 236, 231);">
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true" style="position: absolute; inset: 0px; display: block;">
            @if ($image)
                <img class="fv-card-img" alt="{{ $product->name }}" loading="lazy" src="{{ $image->url('medium') }}" srcset="{{ $image->srcset() }}" sizes="300px" style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: cover; transform: scale(1); transition: transform 700ms cubic-bezier(0.33, 1, 0.68, 1); will-change: transform; backface-visibility: hidden;">
            @endif
        </a>
        @php $saved = app(\App\Services\Wishlist\WishlistService::class)->has($product->id); @endphp
        <form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $product->id]) }}" x-data="wishlistButton({{ $saved ? 'true' : 'false' }})" @submit.prevent="toggle($event)" style="margin: 0px;">
            @csrf
            <button type="submit" class="scpg" :aria-pressed="saved" title="{{ $saved ? 'Remove from wishlist' : 'Add to wishlist' }}" :title="saved ? 'Remove from wishlist' : 'Add to wishlist'" aria-label="Add {{ $product->name }} to wishlist"
                    style="position: absolute; top: 14px; right: 14px; width: 38px; height: 38px; border: none; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(4px); color: rgb(32, 32, 32); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: transform 300ms; z-index: 3;"><span x-text="saved ? '♥' : '♡'">{{ $saved ? '♥' : '♡' }}</span></button>
        </form>
        @if ($brand)
            <a href="{{ store_route('brands.show', ['slug' => $brand->slug]) }}" title="{{ $brand->name }}" style="position: absolute; top: 14px; left: 14px; height: 28px; padding: 0px 8px; background: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; z-index: 3; cursor: pointer;">
                @if ($logo = $brand->logoUrl(small: true))
                    <img alt="{{ $brand->name }}" src="{{ $logo }}" style="height: 17px; width: auto; max-width: 80px; object-fit: contain; display: block;">
                @else
                    <span style="font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; color: rgb(32, 32, 32); white-space: nowrap;">{{ $brand->name }}</span>
                @endif
            </a>
        @endif
        <a href="{{ $url }}" class="fv-card-quick" style="position: absolute; left: 14px; right: 14px; bottom: 14px; height: 42px; border: none; background: rgba(29, 29, 29, 0.92); color: rgb(255, 255, 255); font-size: 11px; letter-spacing: 0.16em; text-transform: uppercase; cursor: pointer; z-index: 3; opacity: 0; transform: translateY(8px); transition: opacity 300ms, transform 300ms; display: flex; align-items: center; justify-content: center;">Quick view</a>
    </div>
    <div style="display: flex; flex-direction: column; gap: 5px;">
        <div class="fv-card-head" style="display: flex; align-items: baseline; justify-content: space-between; gap: 14px;">
            <h3 style="margin: 0px; font-size: 16px; line-height: 22px; font-weight: 600; color: rgb(32, 32, 32); letter-spacing: -0.01em;"><a href="{{ $url }}" style="color: inherit;"><span>{{ $product->name }}</span></a></h3>
            @if ($variant)
                <x-store.fv-price :money="$variant->price" style="display: flex; align-items: center; gap: 0px; font-size: 15px; font-weight: 500; color: rgb(32, 32, 32); white-space: nowrap;" />
                @if ($variant->isOnSale())
                    {{-- What it used to cost, struck through. --}}
                    <span style="font-size: 13px; color: rgb(139, 135, 127); text-decoration: line-through; margin-inline-start: 8px;">
                        <x-store.fv-price :money="$variant->compare_at_price" style="display: inline-flex; align-items: center;" />
                    </span>
                @endif
            @endif
        </div>
        @if ($product->short_description)
            <p style="margin: 0px; font-size: 15px; line-height: 22px; font-weight: 400; color: rgb(139, 135, 127);"><span>{{ \App\Support\RichText::text($product->short_description) }}</span></p>
        @endif
        @if ($product->rating_count > 0)
            <x-store.fv-stars :rating="$product->rating_average" :count="$product->rating_count" />
        @endif
    </div>
</div>
