{{-- Reviews section on the product page. --}}
@php
    $reviews = $product->reviews()->approved()->with('photos')->limit(20)->get();
    $customer = auth('web')->user();
    $mine = $customer ? $product->reviews()->where('user_id', $customer->id)->first() : null;
@endphp
<section id="reviews" class="mt-20 scroll-mt-28 border-t border-line pt-12" aria-labelledby="reviews-heading">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="reviews-heading" class="text-2xl font-semibold tracking-tight">{{ __('store.reviews.title') }}</h2>
            @if ($product->rating_count)
                <p class="mt-2 flex items-center gap-2 text-[15px]">
                    <x-store.stars :rating="$product->rating_average" size="size-5" />
                    <span>{{ trans_choice('store.reviews.summary', $product->rating_count, ['rating' => number_format((float) $product->rating_average, 1), 'count' => $product->rating_count]) }}</span>
                </p>
            @endif
        </div>
    </div>

    <div class="mt-8 grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-7">
            @forelse ($reviews as $review)
                <article class="border-b border-line py-6 first:pt-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-store.stars :rating="$review->rating" />
                        @if ($review->title)<h3 class="font-medium">{{ $review->title }}</h3>@endif
                    </div>
                    <p class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-ink-soft" @if ($review->locale !== app()->getLocale()) lang="{{ $review->locale }}" dir="{{ $review->locale === 'ar' ? 'rtl' : 'ltr' }}" @endif>{{ $review->body }}</p>
                    @if ($review->photos->isNotEmpty())
                        <div class="mt-3 flex gap-2">
                            @foreach ($review->photos as $photo)
                                <a href="{{ $photo->url('large') }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-md">
                                    <img src="{{ $photo->url('thumb') }}" alt="{{ __('store.reviews.photo_by', ['name' => $review->name]) }}" loading="lazy" class="size-20 object-cover transition hover:scale-105">
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <p class="mt-3 text-sm text-stone">
                        {{ $review->name }} · {{ $review->created_at->timezone(config('store.display_timezone'))->translatedFormat('j M Y') }}
                        @if ($review->verified_purchase) · <span class="inline-flex items-center gap-1 text-success"><x-icon name="badge-check" class="size-4" /> {{ __('store.reviews.verified') }}</span>@endif
                    </p>
                </article>
            @empty
                <p class="text-[15px] text-ink-soft">{{ __('store.reviews.none') }}</p>
            @endforelse
        </div>

        <div class="lg:col-span-5">
            <div class="rounded-md bg-wool-soft p-6">
                @if (! $customer)
                    <p class="text-[15px]">{{ __('store.reviews.sign_in') }}</p>
                    <x-store.button :href="store_route('login')" variant="secondary" class="mt-4">{{ __('store.auth.login') }}</x-store.button>
                @elseif ($mine)
                    <p class="text-[15px]">{{ $mine->status === 'approved' ? __('store.reviews.yours_live') : __('store.reviews.yours_pending') }}</p>
                @else
                    <h3 class="text-lg font-semibold">{{ __('store.reviews.write') }}</h3>
                    <form method="POST" action="{{ store_route('reviews.store', ['slug' => $product->slug]) }}" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{ rating: {{ (int) old('rating', 0) }} }">
                        @csrf
                        <fieldset>
                            <legend class="text-sm font-medium">{{ __('store.reviews.rating') }}</legend>
                            <div class="mt-2 flex gap-1" role="radiogroup">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $i }}" x-model.number="rating" class="peer sr-only" @checked(old('rating') == $i) required>
                                        <svg viewBox="0 0 24 24" class="size-8 text-brand-red peer-focus-visible:rounded peer-focus-visible:ring-2 peer-focus-visible:ring-ink" aria-hidden="true">
                                            <path :fill="rating >= {{ $i }} ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 17.3l-5.9 3.2 1.3-6.5L2.5 9.4l6.6-.8z"/>
                                        </svg>
                                        <span class="sr-only">{{ trans_choice('store.reviews.star_choice', $i, ['count' => $i]) }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </fieldset>
                        <x-store.input name="title" :label="__('store.reviews.review_title')" :value="old('title')" maxlength="120" />
                        <div class="space-y-2">
                            <label for="body" class="block text-sm font-medium">{{ __('store.reviews.body') }}</label>
                            <textarea id="body" name="body" rows="4" required minlength="10" maxlength="2000" class="store-field @error('body') store-field-error @enderror">{{ old('body') }}</textarea>
                            @error('body')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-2">
                            <label for="photos" class="block text-sm font-medium">{{ __('store.reviews.photos') }} <span class="font-normal text-stone">({{ __('store.common.optional') }})</span></label>
                            <input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-paper file:px-4 file:py-2 file:text-sm file:font-medium">
                            <p class="text-sm text-stone">{{ __('store.reviews.photos_help') }}</p>
                            @error('photos')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                            @error('photos.*')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-store.button>{{ __('store.reviews.submit') }}</x-store.button>
                        <p class="text-sm text-stone">{{ __('store.reviews.moderated') }}</p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
