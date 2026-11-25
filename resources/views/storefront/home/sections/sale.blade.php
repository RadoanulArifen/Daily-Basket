{{-- Home page section: sale (order and visibility: Admin → Online Store → Customize) --}}
@if ($hasSale)
    <section class="mx-auto max-w-shop px-4 pt-20 sm:px-6 lg:px-10 lg:pt-28">
        <div class="relative overflow-hidden rounded-[2rem] bg-brand-red px-8 py-12 text-paper sm:px-14 sm:py-16">
            <div class="weave-band absolute inset-x-0 top-0 [--brand-red-a:var(--color-paper)] [--brand-red-b:var(--color-ink)] [--brand-red-bg:var(--color-brand-red)]" aria-hidden="true"></div>
            <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-center">
                <div>
                    <p class="text-[13px] font-semibold tracking-[0.18em] text-paper/75 uppercase rtl:tracking-normal">{{ __('store.home.sale_eyebrow') }}</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-5xl">{{ __('store.home.sale_title') }}</h2>
                    <p class="mt-3 max-w-lg text-[17px] text-paper/85">{{ __('store.home.sale_text') }}</p>
                </div>
                <a href="{{ store_route('collections.show', ['slug' => 'sale']) }}" class="inline-flex h-12 shrink-0 items-center gap-2 rounded-full bg-paper px-7 text-[15px] font-medium text-ink hover:bg-wool">
                    {{ __('store.home.shop_sale') }} <x-icon name="arrow-right" class="size-4 rtl:rotate-180" />
                </a>
            </div>
        </div>
    </section>
@endif
