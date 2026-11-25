@props(['product', 'label' => false])

@php $saved = app(\App\Services\Wishlist\WishlistService::class)->has($product->id); @endphp

<form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $product->id]) }}"
      x-data="wishlistButton(@js($saved))" @submit.prevent="toggle($event)" {{ $attributes }}>
    @csrf
    <button type="submit" :aria-pressed="saved" aria-pressed="{{ $saved ? 'true' : 'false' }}" :disabled="busy"
            class="{{ $label ? 'inline-flex h-12 items-center gap-2 rounded-md border border-line px-4 text-[15px] hover:border-ink' : 'flex size-9 items-center justify-center rounded-full bg-paper/90 shadow-sm backdrop-blur hover:bg-paper' }}"
            aria-label="{{ __('store.wishlist.save_named', ['name' => $product->name]) }}">
        <x-icon name="heart" class="size-[18px] transition {{ $saved ? 'fill-brand-red text-brand-red' : '' }}" x-bind:class="saved ? 'fill-brand-red text-brand-red' : 'fill-transparent text-ink'" />
        @if ($label)
            <span x-text="saved ? @js(__('store.wishlist.saved')) : @js(__('store.wishlist.save'))">{{ $saved ? __('store.wishlist.saved') : __('store.wishlist.save') }}</span>
        @endif
    </button>
</form>
