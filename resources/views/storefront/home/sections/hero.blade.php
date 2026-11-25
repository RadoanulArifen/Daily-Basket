{{-- Home page section: hero (order and visibility: Admin → Online Store → Customize) --}}
@if ($heroBanner)
    <section class="relative isolate overflow-hidden bg-ink text-paper">
        @if ($heroBanner->image)
            <img src="{{ $heroBanner->image->url('large') }}" srcset="{{ $heroBanner->image->srcset() }}" sizes="100vw" alt="" fetchpriority="high" class="absolute inset-0 -z-10 size-full object-cover opacity-60">
        @endif
        <div class="mx-auto max-w-shop px-4 py-24 sm:px-6 md:py-32 lg:px-10 lg:py-40">
            <h1 class="max-w-2xl text-4xl leading-[1.05] font-semibold tracking-tight text-balance sm:text-6xl">{{ $heroBanner->title }}</h1>
            @if ($heroBanner->text)<p class="mt-6 max-w-xl text-lg leading-relaxed text-paper/85">{{ $heroBanner->text }}</p>@endif
            @if ($heroBanner->href() && $heroBanner->button_label)
                <a href="{{ $heroBanner->href() }}" class="mt-10 inline-flex h-12 items-center rounded-full bg-paper px-7 text-[15px] font-medium text-ink hover:bg-wool">{{ $heroBanner->button_label }}</a>
            @endif
        </div>
    </section>
@else
    <section class="relative overflow-hidden bg-wool-soft">
        <div class="mx-auto grid max-w-shop items-center gap-12 px-4 pt-10 pb-16 sm:px-6 lg:grid-cols-12 lg:gap-10 lg:px-10 lg:pt-16 lg:pb-24">
            <div class="lg:col-span-5">
                <p class="inline-flex items-center gap-2 rounded-full border border-ink/10 bg-paper/70 px-3 py-1.5 text-[13px] font-medium text-ink-soft">
                    <span class="size-1.5 rounded-full bg-brand-red"></span> {{ __('store.home.eyebrow') }}
                </p>
                <h1 class="mt-6 text-[2.6rem] leading-[1.04] font-semibold tracking-tight text-balance sm:text-6xl xl:text-7xl">{{ __('store.home.hero_title') }}</h1>
                <p class="mt-6 max-w-md text-lg leading-relaxed text-ink-soft">{{ settings()->trans('general.tagline') ?: __('store.home.hero_text') }}</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ store_route('collections.show', ['slug' => 'new-in']) }}" class="inline-flex h-12 items-center gap-2 rounded-full bg-ink px-7 text-[15px] font-medium text-paper transition hover:bg-brand-red">
                        {{ __('store.home.shop_new') }} <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
                    </a>
                    <a href="{{ store_route('shop') }}" class="inline-flex h-12 items-center rounded-full border border-ink/20 px-7 text-[15px] font-medium hover:border-ink">{{ __('store.home.shop_all') }}</a>
                </div>
                <ul class="mt-10 flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-soft">
                    @foreach (['truck' => 'trust_delivery', 'coins' => 'trust_cod', 'rotate-ccw' => 'trust_returns'] as $icon => $key)
                        <li class="inline-flex items-center gap-2"><x-icon :name="$icon" class="size-4 text-brand-red" /> {{ __("store.home.{$key}") }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="relative lg:col-span-7">
                <div class="overflow-hidden rounded-[2rem] shadow-[0_30px_80px_-30px_rgba(34,38,42,.45)]">
                    <img src="{{ $sceneHero }}" alt="" width="1600" height="1200" fetchpriority="high" class="aspect-[4/3] w-full object-cover">
                </div>
                @if ($heroProduct)
                    @php $img = $heroProduct->media->first(); @endphp
                    <a href="{{ store_route('products.show', ['slug' => $heroProduct->slug]) }}"
                       class="absolute -bottom-6 start-4 flex max-w-[18rem] items-center gap-3 rounded-2xl bg-paper/95 p-3 pe-5 shadow-xl backdrop-blur transition hover:-translate-y-0.5 sm:start-8">
                        <img src="{{ $img->url('thumb') }}" alt="" class="size-16 shrink-0 rounded-xl bg-wool-soft object-cover">
                        <span class="min-w-0">
                            <span class="block text-[11px] font-medium tracking-wider text-brand-red uppercase rtl:tracking-normal">{{ __('store.home.hero_pick') }}</span>
                            <span class="block truncate text-[15px] font-medium">{{ $heroProduct->name }}</span>
                            <x-store.price :product="$heroProduct" class="text-sm!" />
                        </span>
                    </a>
                @endif
                <x-store.brand-mark class="absolute -top-5 end-6 size-14 drop-shadow-lg max-sm:hidden" />
            </div>
        </div>
        <div class="weave-band" aria-hidden="true"></div>
    </section>
@endif
