{{-- Home page section: categories (order and visibility: Admin → Online Store → Customize) --}}
@if ($categories->isNotEmpty())
    <section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28" aria-labelledby="categories-heading">
        <div class="flex items-end justify-between gap-6">
            <div>
                <p class="text-[13px] font-semibold tracking-[0.18em] text-brand-red uppercase rtl:tracking-normal">{{ __('store.home.categories_eyebrow') }}</p>
                <h2 id="categories-heading" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.home.shop_by_category') }}</h2>
            </div>
            <a href="{{ store_route('shop') }}" class="hidden shrink-0 text-[15px] font-medium underline decoration-ink/30 underline-offset-4 hover:decoration-ink sm:inline">{{ __('store.home.view_all') }}</a>
        </div>
        <ul class="mt-10 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4 lg:grid-rows-2">
            @foreach ($categories->take(5) as $category)
                @php
                    $tile = $category->image ?? ($category->relationLoaded('coverProduct') ? $category->getRelation('coverProduct')?->media->first() : null);
                    $big = $loop->first;
                    $count = $categoryCounts[$category->id] ?? 0;
                @endphp
                @php $wide = ! $big && $categories->count() === 3; @endphp
                <li @class(['col-span-2 lg:row-span-2' => $big, 'lg:col-span-2' => $wide])>
                    <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" class="group relative block h-full overflow-hidden rounded-3xl bg-wool">
                        @if ($tile)
                            <img src="{{ $tile->url($big ? 'large' : 'medium') }}" alt="" loading="lazy" @class(['size-full object-cover transition duration-700 group-hover:scale-105', 'aspect-[4/5] lg:aspect-auto lg:min-h-full' => $big, 'aspect-square' => ! $big, 'lg:aspect-[2/1] lg:object-[center_62%]' => $wide])>
                        @endif
                        <span class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/10 to-transparent"></span>
                        <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-5 text-paper sm:p-6">
                            <span>
                                <span @class(['block font-semibold tracking-tight', 'text-2xl sm:text-3xl' => $big, 'text-lg sm:text-xl' => ! $big])>{{ $category->name }}</span>
                                <span class="mt-1 block text-sm text-paper/80">{{ trans_choice('store.listing.count', $count, ['count' => $count]) }}</span>
                            </span>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper text-ink transition group-hover:bg-brand-red group-hover:text-paper">
                                <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
