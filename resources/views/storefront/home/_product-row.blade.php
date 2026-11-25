{{-- Home page product row: swipeable on phones, grid on larger screens. --}}
<section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28" aria-labelledby="{{ $id }}-heading">
    <div class="flex items-end justify-between gap-6">
        <div>
            <p class="text-[13px] font-semibold tracking-[0.18em] text-brand-red uppercase rtl:tracking-normal">{{ $eyebrow }}</p>
            <h2 id="{{ $id }}-heading" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h2>
        </div>
        <a href="{{ $url }}" class="inline-flex shrink-0 items-center gap-1.5 text-[15px] font-medium underline decoration-ink/30 underline-offset-4 hover:decoration-ink">
            {{ __('store.home.view_all') }}
        </a>
    </div>
    <ul class="-mx-4 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 [scrollbar-width:none] sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-6 sm:overflow-visible sm:px-0 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($products as $product)
            <li class="w-[70%] shrink-0 snap-start sm:w-auto"><x-store.product-card :product="$product" /></li>
        @endforeach
    </ul>
</section>
