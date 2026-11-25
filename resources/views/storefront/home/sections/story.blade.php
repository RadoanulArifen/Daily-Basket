{{-- Home page section: story (order and visibility: Admin → Online Store → Customize) --}}
<section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28" aria-labelledby="story-heading">
    <div class="grid items-center overflow-hidden rounded-[2rem] bg-wool-soft lg:grid-cols-2">
        <img src="{{ $sceneStory }}" alt="" loading="lazy" class="aspect-[4/3] size-full object-cover lg:aspect-auto lg:min-h-full">
        <div class="px-8 py-12 sm:px-14 sm:py-16">
            <p class="text-[13px] font-semibold tracking-[0.18em] text-brand-red uppercase rtl:tracking-normal">{{ __('store.home.story_eyebrow') }}</p>
            <h2 id="story-heading" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.home.story_title') }}</h2>
            <p class="mt-4 max-w-lg text-[17px] text-ink-soft">{{ __('store.home.story_text') }}</p>
            <dl class="mt-8 grid max-w-md grid-cols-3 gap-6">
                @foreach ([1, 2, 3] as $n)
                    <div>
                        <dt class="text-sm text-stone">{{ __("store.home.stat_{$n}_label") }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight">{{ __("store.home.stat_{$n}_value") }}</dd>
                    </div>
                @endforeach
            </dl>
            <a href="{{ store_route('pages.show', ['slug' => 'about']) }}" class="mt-8 inline-flex h-12 items-center gap-2 rounded-full bg-ink px-7 text-[15px] font-medium text-paper hover:bg-brand-red">
                {{ __('store.home.story_cta') }} <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
            </a>
        </div>
    </div>
</section>
