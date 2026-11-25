{{--
    Product listing with filters and sort, shared by collections and search.
    Plain GET forms: they work without JavaScript and every filtered page has a shareable URL.
    Expects: $products (paginator), $filters, $sort; optional $keep (params to preserve, e.g. ['q' => ...]).
--}}
@php
    $currency = app(\App\Services\Currency\CurrencyService::class)->current();
    $keep ??= [];
    $active = ($filters['min'] !== null) + ($filters['max'] !== null) + (int) $filters['in_stock'] + (int) $filters['sale'];
    $filterParams = array_filter(['min' => $filters['min'], 'max' => $filters['max'], 'in_stock' => $filters['in_stock'] ? 1 : null, 'sale' => $filters['sale'] ? 1 : null], fn ($v) => $v !== null);
    // Desktop filters apply as soon as a box is ticked (phones use the Show results button).
    $autoSubmit = 'if (window.innerWidth >= 1024) $el.form.submit()';
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="mt-8">
    <div class="flex items-center justify-between gap-4 border-y border-line py-3">
        <button type="button" @click="open = true" class="inline-flex items-center gap-2 text-[15px] lg:hidden" :aria-expanded="open" aria-controls="filters">
            <x-icon name="sliders-horizontal" class="size-5" />
            {{ __('store.listing.filter') }}@if ($active) <span class="rounded-full bg-ink px-1.5 text-xs text-paper">{{ $active }}</span>@endif
        </button>
        <p class="hidden text-[15px] text-ink-soft lg:block">{{ trans_choice('store.listing.count', $products->total(), ['count' => $products->total()]) }}</p>

        <form method="GET" class="flex items-center gap-2">
            @foreach ($keep + $filterParams as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <label for="sort" class="text-[15px] text-ink-soft max-sm:sr-only">{{ __('store.listing.sort_by') }}</label>
            <select id="sort" name="sort" x-data @change="$el.form.submit()" class="rounded-md border border-line bg-white py-2 ps-3 pe-8 text-[15px] focus:border-ink focus:outline-none">
                @foreach (\App\Services\Catalog\StorefrontCatalog::SORTS as $option)
                    <option value="{{ $option }}" @selected($sort === $option)>{{ __("store.listing.sort.{$option}") }}</option>
                @endforeach
            </select>
            <noscript><button class="rounded-md border border-ink px-3 py-2 text-[15px]">{{ __('store.common.apply') }}</button></noscript>
        </form>
    </div>

    <div class="mt-8 lg:grid lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-start lg:gap-x-12">
        {{-- Filters: slide-over on mobile, sidebar on desktop --}}
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-ink/40 lg:hidden" @click="open = false"></div>
        <form method="GET" id="filters" aria-label="{{ __('store.listing.filters') }}"
              class="fixed inset-y-0 end-0 z-50 flex w-[85vw] max-w-sm translate-x-full flex-col bg-paper transition-transform duration-200 rtl:-translate-x-full lg:sticky lg:top-28 lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:bg-transparent lg:transition-none rtl:lg:translate-x-0"
              :class="open && 'translate-x-0!'">
            @foreach ($keep + array_filter(['sort' => $filters['sort']]) as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-5 lg:hidden">
                <p class="text-lg font-semibold">{{ __('store.listing.filter') }}</p>
                <button type="button" @click="open = false" class="-me-2 p-2" aria-label="{{ __('store.common.close') }}"><x-icon name="x" class="size-6" /></button>
            </div>

            <div class="flex-1 space-y-8 overflow-y-auto px-5 py-6 lg:overflow-visible lg:p-0">
                <fieldset>
                    <legend class="text-[15px] font-medium">{{ __('store.listing.availability') }}</legend>
                    <label class="mt-3 flex items-center gap-3 text-[15px]">
                        <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock']) x-data @change="{{ $autoSubmit }}" class="size-4 accent-ink">
                        {{ __('store.listing.in_stock') }}
                    </label>
                    <label class="mt-2.5 flex items-center gap-3 text-[15px]">
                        <input type="checkbox" name="sale" value="1" @checked($filters['sale']) x-data @change="{{ $autoSubmit }}" class="size-4 accent-ink">
                        {{ __('store.listing.on_sale') }}
                    </label>
                </fieldset>

                <fieldset>
                    <legend class="text-[15px] font-medium">{{ __('store.listing.price') }} <span class="font-normal text-stone">({{ $currency->code }})</span></legend>
                    <div class="mt-3 flex items-center gap-2">
                        <label class="sr-only" for="min">{{ __('store.listing.min') }}</label>
                        <input id="min" type="number" name="min" min="0" step="1" inputmode="numeric" value="{{ $filters['min'] }}" placeholder="{{ __('store.listing.min') }}" class="store-field py-2! text-[15px]!" dir="ltr">
                        <span class="text-stone" aria-hidden="true">–</span>
                        <label class="sr-only" for="max">{{ __('store.listing.max') }}</label>
                        <input id="max" type="number" name="max" min="0" step="1" inputmode="numeric" value="{{ $filters['max'] }}" placeholder="{{ __('store.listing.max') }}" class="store-field py-2! text-[15px]!" dir="ltr">
                    </div>
                </fieldset>
            </div>

            <div class="flex shrink-0 gap-3 border-t border-line p-5 lg:mt-6 lg:flex-col lg:border-0 lg:p-0">
                <button class="h-11 flex-1 rounded-md bg-ink px-5 text-[15px] font-medium text-paper hover:bg-ink-soft">{{ __('store.listing.show_results') }}</button>
                @if ($active)
                    <a href="{{ url()->current().($keep || $filters['sort'] ? '?'.http_build_query($keep + array_filter(['sort' => $filters['sort']])) : '') }}"
                       class="inline-flex h-11 items-center justify-center rounded-md border border-line px-5 text-[15px] hover:border-ink">{{ __('store.listing.clear') }}</a>
                @endif
            </div>
        </form>

        {{-- Results --}}
        <div class="lg:min-w-0">
            @if ($products->isEmpty())
                <div class="rounded-md border border-dashed border-line px-6 py-16 text-center">
                    <p class="text-lg font-medium">{{ __('store.listing.empty_title') }}</p>
                    <p class="mt-2 text-[15px] text-ink-soft">{{ $active ? __('store.listing.empty_filtered') : __('store.listing.empty_text') }}</p>
                    <x-store.button :href="$active ? url()->current().($keep ? '?'.http_build_query($keep) : '') : store_route('shop')" variant="secondary" class="mt-6">
                        {{ $active ? __('store.listing.clear') : __('store.listing.browse_all') }}
                    </x-store.button>
                </div>
            @else
                <x-store.product-grid :products="$products" :eager-count="4" class="lg:grid-cols-3 xl:grid-cols-4" />
                {{ $products->links('components.store.pagination') }}
            @endif
        </div>
    </div>
</div>
