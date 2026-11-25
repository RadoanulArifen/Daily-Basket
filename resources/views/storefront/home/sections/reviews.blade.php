{{-- Home page section: reviews (order and visibility: Admin → Online Store → Customize).
     The three latest approved 4–5★ reviews; nothing is shown until there are some. --}}
@if (($reviews ?? collect())->isNotEmpty())
    <section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28" aria-labelledby="reviews-heading">
        <p class="text-[13px] font-semibold tracking-[0.18em] text-brand-red uppercase rtl:tracking-normal">{{ __('store.home.reviews_eyebrow') }}</p>
        <h2 id="reviews-heading" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.home.reviews_title') }}</h2>
        <ul class="mt-10 grid gap-4 sm:gap-6 md:grid-cols-3">
            @foreach ($reviews as $review)
                <li class="flex flex-col rounded-3xl bg-wool-soft p-6 sm:p-8">
                    <x-store.stars :rating="$review->rating" size="size-4" />
                    @if ($review->title)
                        <p class="mt-4 font-semibold">{{ $review->title }}</p>
                    @endif
                    <p class="mt-2 flex-1 text-[15px] text-ink-soft">{{ \Illuminate\Support\Str::limit((string) $review->body, 220) }}</p>
                    <p class="mt-6 text-sm text-stone">
                        {{ $review->name }}
                        @if ($review->product)
                            · <a href="{{ store_route('products.show', ['slug' => $review->product->slug]) }}" class="underline decoration-ink/30 underline-offset-4 hover:decoration-ink">{{ $review->product->name }}</a>
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    </section>
@endif
