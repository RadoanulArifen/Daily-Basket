@extends('storefront.layouts.app')

{{-- Furnivo listing (filters, sort, grid, pages) — markup and styles from the design.
     Every filter is a link, so it works without JavaScript and can be shared or bookmarked. --}}
@section('furnivo', true)
@section('title', $metaTitle ?? $title)
@php $metaText = $metaDescription ?? \App\Support\RichText::text($description); @endphp
@if (filled($metaText))
    @section('meta_description', \Illuminate\Support\Str::limit(strip_tags($metaText), 160))
@endif
{{-- Filtered and sorted views should not compete with the plain page in search results. --}}
@if (request()->hasAny(array_merge(['sort', 'min', 'max', 'in_stock', 'sale', 'band', 'availability', 'brand', 'colour', 'cat'], array_keys(\App\Services\Catalog\ProductFacets::GROUPS))))
    @section('noindex', true)
@endif
@section('canonical', url()->current().($products->currentPage() > 1 ? '?page='.$products->currentPage() : ''))

@php
    $facets = $facets ?? ['groups' => [], 'colours' => [], 'brands' => [], 'availability' => [], 'total' => $products->total()];
    $brands = $brands ?? collect();
    $chosen = $filters;
    $parent = ($category ?? null)?->parent;
    $symbol = app(\App\Services\Currency\CurrencyService::class)->current()->symbol;
    // The search page reuses this listing with its own breadcrumb and eyebrow.
    $eyebrow = $eyebrow ?? 'Collection';
    $crumbs = $crumbs ?? null;
    $navCategories = \App\Models\Category::query()->active()->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
        ->orderBy('sort_order')->limit(6)->get();
    $label = 'font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; color: rgb(32, 32, 32); margin-bottom: 16px;';
    $row = 'cursor: pointer; display: flex; align-items: center; gap: 12px; font-size: 15px; color: rgb(32, 32, 32);';
    $countStyle = 'color: rgb(168, 164, 158); font-size: 13px;';
    $box = 'width: 18px; height: 18px; flex-shrink: 0; color: rgb(255, 255, 255); font-size: 11px; display: flex; align-items: center; justify-content: center; transition: box-shadow 220ms, background 220ms;';
    // Every filter link keeps the others and drops the page number.
    $link = function (array $changes) {
        $q = array_merge(request()->except('page'), $changes);
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '' && $v !== []);
        return url()->current().($q ? '?'.http_build_query($q) : '');
    };
    $toggle = function (string $key, string $value) use ($link) {
        $current = (array) request()->query($key, []);
        $next = in_array($value, $current, true) ? array_values(array_diff($current, [$value])) : [...$current, $value];
        return $link([$key => $next ?: null]);
    };
    $isOn = fn (string $key, string $value) => in_array($value, (array) request()->query($key, []), true);
@endphp

@section('content')
    <section style="width: 100%; background: rgb(245, 244, 240); padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(30px, 4vw, 44px);">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; margin-bottom: 28px;">
            <a href="{{ store_route('home') }}" class="scp7" style="color: rgb(102, 102, 102); cursor: pointer; transition: color 200ms;"><span>{{ __('store.theme.home') }}</span></a>
            @if ($crumbs)
                @foreach ($crumbs as $crumb)
                    <span style="color: rgb(201, 197, 191);">/</span>
                    <span class="scp7" @if ($loop->last) aria-current="page" @endif style="color: rgb({{ $loop->last ? '32, 32, 32' : '102, 102, 102' }}); cursor: default; transition: color 200ms;"><span>{{ $crumb }}</span></span>
                @endforeach
            @else
                @if ($parent)
                    <span style="color: rgb(201, 197, 191);">/</span>
                    <a href="{{ store_route('collections.show', ['slug' => $parent->slug]) }}" class="scp7" style="color: rgb(102, 102, 102); cursor: pointer; transition: color 200ms;"><span>{{ $parent->name }}</span></a>
                @endif
                <span style="color: rgb(201, 197, 191);">/</span>
                <span class="scp7" aria-current="page" style="color: rgb(32, 32, 32); cursor: default; transition: color 200ms;"><span>{{ $title }}</span></span>
            @endif
        </nav>
        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap;">
            <div style="max-width: 60ch;">
                <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 14px;"><span>{{ $eyebrow }}</span></div>
                <h1 style="margin: 0px 0px 14px; font-size: clamp(36px, 4.6vw, 60px); line-height: 1.02; font-weight: 800; letter-spacing: -0.03em;"><span>{{ $title }}</span></h1>
                @if ($blurb = \App\Support\RichText::text($description ?: $parent?->description ?? ''))
                    <p style="margin: 0px; font-size: 16px; line-height: 26px; color: rgb(102, 102, 102);"><span>{{ $blurb }}</span></p>
                @endif
            </div>
            <div style="font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(102, 102, 102); white-space: nowrap;"><span>{{ $products->total() }}</span> pieces</div>
        </div>
    </section>

    <section class="pdp-cols fx-pdp-layout" style="width: 100%; padding: clamp(28px, 4vw, 44px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px); display: grid; grid-template-columns: 230px 1fr; gap: clamp(28px, 4vw, 56px); align-items: start;" x-data="{ open: false, brandSearch: '' }">
        <div>
            <button type="button" class="filter-toggle" @click="open = ! open" :aria-expanded="open" style="align-items: center; justify-content: space-between; padding: 14px 18px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); cursor: pointer; margin-bottom: 18px; width: 100%;">
                <span style="font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(32, 32, 32);">{{ __('store.theme.filters') }}</span>
                <span style="font-size: 13px; color: rgb(32, 32, 32); transition: transform 250ms;" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }">⌄</span>
            </button>
            <aside class="filter-side fx-filter-panel" :class="open || 'filter-collapsed'" style="position: sticky; top: 138px; border: 1px solid rgb(236, 236, 236); background: rgb(255, 255, 255);" aria-label="{{ __('store.theme.filters') }}">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                    <span style="font-size: 12px; letter-spacing: 0.22em; text-transform: uppercase; color: rgb(32, 32, 32); font-weight: 600;">{{ __('store.theme.filter') }}</span>
                    <a href="{{ url()->current() }}" class="scp7" style="cursor: pointer; font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(102, 102, 102); transition: color 200ms;">{{ __('store.theme.clear_all') }}</a>
                </div>

                <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                    <div style="{{ $label }}">{{ __('store.theme.categories') }}</div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <a href="{{ $link(['cat' => null]) }}" style="{{ $row }}">
                            <span style="{{ $box }} {{ empty($chosen['cats']) ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}">{{ empty($chosen['cats']) ? '✓' : '' }}</span>
                            <span style="flex: 1 1 0%;">All</span>
                            <span style="{{ $countStyle }}"><span>{{ $facets['total'] }}</span></span>
                        </a>
                        @foreach ($navCategories as $navCategory)
                            @php $on = $isOn('cat', (string) $navCategory->id); $count = $facets['categories'][$navCategory->id] ?? 0; @endphp
                            <div x-data="{ open: {{ $navCategory->children->contains(fn ($child) => $isOn('cat', (string) $child->id)) ? 'true' : 'false' }} }">
                                <div style="display: flex; align-items: center; gap: 12px; font-size: 15px; color: rgb(32, 32, 32);">
                                    <a href="{{ $toggle('cat', (string) $navCategory->id) }}" style="{{ $box }} cursor: pointer; {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}" aria-label="{{ $navCategory->name }}">{{ $on ? '✓' : '' }}</a>
                                    <a href="{{ $toggle('cat', (string) $navCategory->id) }}" style="flex: 1 1 0%; color: inherit;"><span>{{ $navCategory->name }}</span></a>
                                    @if ($count)<span style="{{ $countStyle }}"><span>{{ $count }}</span></span>@endif
                                    @if ($navCategory->children->isNotEmpty())
                                        <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="{{ __('store.listing.filters') }}: {{ $navCategory->name }}" style="border: none; background: transparent; cursor: pointer; width: 20px; font-size: 12px; color: rgb(139, 135, 127); padding: 0px; transition: transform 250ms;" :style="{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }">⌄</button>
                                    @endif
                                </div>
                                @if ($navCategory->children->isNotEmpty())
                                    <div x-show="open" x-cloak>
                                    <div style="display: flex; flex-direction: column; gap: 12px; padding: 12px 0px 0px 30px;">
                                        @foreach ($navCategory->children as $child)
                                            @php $childOn = $isOn('cat', (string) $child->id); $childCount = $facets['categories'][$child->id] ?? 0; @endphp
                                            <a href="{{ $toggle('cat', (string) $child->id) }}" style="{{ $row }} font-size: 14px;">
                                                <span style="{{ $box }} {{ $childOn ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}">{{ $childOn ? '✓' : '' }}</span>
                                                <span style="flex: 1 1 0%;"><span>{{ $child->name }}</span></span>
                                                @if ($childCount)<span style="{{ $countStyle }}"><span>{{ $childCount }}</span></span>@endif
                                            </a>
                                        @endforeach
                                    </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($brands->isNotEmpty())
                    <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                        <div style="{{ $label }}">{{ __('store.theme.brand') }}</div>
                        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid rgb(228, 225, 218); padding: 0px 12px; margin-bottom: 14px;">
                            <span aria-hidden="true" style="color: rgb(168, 164, 158); font-size: 13px;">⌕</span>
                            <input type="search" x-model="brandSearch" placeholder="{{ __('store.theme.search_brands') }}" aria-label="{{ __('store.theme.search_brands') }}" style="flex: 1 1 0%; border: none; background: transparent; font-size: 13px; color: rgb(32, 32, 32); outline: none; height: 38px;">
                        </div>
                        <div class="no-sb" style="display: flex; flex-direction: column; gap: 14px; max-height: 190px; overflow-y: auto;">
                            @foreach ($brands as $brand)
                                @php $on = $isOn('brand', (string) $brand->id); @endphp
                                <div x-show="! brandSearch || @js(mb_strtolower($brand->name)).includes(brandSearch.toLowerCase())">
                                <a href="{{ $toggle('brand', (string) $brand->id) }}" style="{{ $row }}" @if ($on) aria-current="true" @endif>
                                    <span style="{{ $box }} {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}">{{ $on ? '✓' : '' }}</span>
                                    <span style="flex: 1 1 0%;"><span>{{ $brand->name }}</span></span>
                                    <span style="{{ $countStyle }}"><span>{{ $facets['brands'][$brand->id] ?? 0 }}</span></span>
                                </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ($facets['groups'] as $id => $group)
                    @php $options = collect($group['options'])->filter(fn ($n) => $n > 0); @endphp
                    @continue ($options->isEmpty())
                    <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                        <div style="{{ $label }}"><span>{{ $group['name'] }}</span></div>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            @foreach ($options as $option => $count)
                                @php $on = $isOn($id, (string) $option); @endphp
                                <a href="{{ $toggle($id, (string) $option) }}" style="{{ $row }}" @if ($on) aria-current="true" @endif>
                                    <span style="{{ $box }} {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}">{{ $on ? '✓' : '' }}</span>
                                    <span style="flex: 1 1 0%;"><span>{{ $option }}</span></span>
                                    <span style="{{ $countStyle }}"><span>{{ $count }}</span></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @php $colours = collect($facets['colours'])->filter(fn ($n) => $n > 0); @endphp
                @if ($colours->isNotEmpty())
                    <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                        <div style="{{ $label }}">Colour &amp; Material</div>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            @foreach ($colours as $key => $count)
                                @php $colour = \App\Services\Catalog\ProductFacets::COLOURS[$key]; $on = $isOn('colour', $key); @endphp
                                <a href="{{ $toggle('colour', $key) }}" style="{{ $row }}" @if ($on) aria-current="true" @endif>
                                    <span style="width: 18px; height: 18px; flex-shrink: 0; border-radius: 50%; background: {{ $colour['swatch'] }}; transition: box-shadow 220ms; {{ $on ? 'box-shadow: rgb(255, 255, 255) 0px 0px 0px 2px inset, rgb(29, 29, 29) 0px 0px 0px 2px;' : 'box-shadow: rgba(0, 0, 0, 0.12) 0px 0px 0px 1px inset;' }}"></span>
                                    <span style="flex: 1 1 0%;"><span>{{ $colour['name'] }}</span></span>
                                    <span style="{{ $countStyle }}"><span>{{ $count }}</span></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                    <div style="{{ $label }}">Price</div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @foreach (\App\Services\Catalog\ProductFacets::PRICE_BANDS as $key => $band)
                            @php $on = (request()->query('band', 'all') === $key); @endphp
                            <a href="{{ $link(['band' => $key === 'all' ? null : $key, 'min' => null, 'max' => null]) }}" style="{{ $row }} {{ $on ? '' : 'color: rgb(102, 102, 102);' }} transition: color 200ms;">
                                <span style="width: 18px; height: 18px; flex-shrink: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: box-shadow 220ms; {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 2px inset;' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset;' }}">
                                    @if ($on)<span style="width: 8px; height: 8px; border-radius: 50%; background: rgb(29, 29, 29);"></span>@endif
                                </span>
                                <span>{{ $key === 'all' ? $band['label'] : str_replace(['Under ', '5,000 –', '15,000 +'], ['Under '.$symbol, $symbol.'5,000 –', $symbol.'15,000 +'], $band['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div style="padding: 22px; border-bottom: 1px solid rgb(228, 225, 218);">
                    <div style="{{ $label }}">{{ __('store.theme.availability') }}</div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @foreach (['' => 'All pieces', 'in_stock' => 'Available now', 'made_to_order' => 'Made to Order'] as $key => $text)
                            @php $on = (string) request()->query('availability', '') === $key; @endphp
                            <a href="{{ $link(['availability' => $key ?: null]) }}" style="{{ $row }} {{ $on ? '' : 'color: rgb(102, 102, 102);' }} transition: color 200ms;">
                                <span style="width: 18px; height: 18px; flex-shrink: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: box-shadow 220ms; {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 2px inset;' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset;' }}">
                                    @if ($on)<span style="width: 8px; height: 8px; border-radius: 50%; background: rgb(29, 29, 29);"></span>@endif
                                </span>
                                <span>{{ $text }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div style="padding: 22px;">
                    <div style="{{ $label }}">Offers</div>
                    @php $on = request()->boolean('sale'); @endphp
                    <a href="{{ $link(['sale' => $on ? null : 1]) }}" style="{{ $row }}">
                        <span style="{{ $box }} {{ $on ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset; background: rgb(29, 29, 29);' : 'box-shadow: rgb(201, 197, 191) 0px 0px 0px 1px inset; background: transparent;' }}">{{ $on ? '✓' : '' }}</span>
                        <span style="flex: 1 1 0%;">On offer</span>
                        @if ($facets['offers'] ?? 0)<span style="{{ $countStyle }}"><span>{{ $facets['offers'] }}</span></span>@endif
                    </a>
                </div>
            </aside>
        </div>

        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 26px;">
                <div style="font-size: 24px; line-height: 1.2; font-weight: 700; letter-spacing: -0.01em; color: rgb(32, 32, 32);">
                    <span style="color: rgb(102, 102, 102);">Search results for</span> <span>{{ $title }}</span>
                </div>
                <div style="position: relative; display: inline-block;">
                    <select id="listing-sort" aria-label="{{ __('store.listing.sort_by') }}" x-data @change="window.location = $event.target.value" style="appearance: none; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); padding: 10px 40px 10px 14px; font-size: 14px; color: rgb(32, 32, 32); cursor: pointer;">
                        @foreach (['featured' => 'Sort — Featured', 'price_asc' => 'Price — Low to High', 'price_desc' => 'Price — High to Low', 'rating' => 'Highest Rated', 'newest' => 'Newest'] as $key => $text)
                            <option value="{{ $link(['sort' => $key]) }}" @selected(($sort ?? 'featured') === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                    <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; color: rgb(32, 32, 32); display: flex;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg></span>
                </div>
            </div>

            @if ($products->isEmpty())
                <p style="margin: 0px; font-size: 16px; color: rgb(102, 102, 102);">{{ filled($filters['q'] ?? '') ? __('store.search.no_results_text') : __('store.listing.empty_filtered') }}</p>
            @else
                <div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(24px, 3vw, 44px) 28px;">
                    @foreach ($products as $product)
                        <x-store.product-card :product="$product" />
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <nav style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: clamp(48px, 6vw, 72px);" aria-label="{{ 'Pages' }}">
                        @foreach (range(1, $products->lastPage()) as $page)
                            @php $current = $page === $products->currentPage(); @endphp
                            <a href="{{ $products->url($page) }}" class="scp8" @if ($current) aria-current="page" @endif style="min-width: 44px; height: 44px; padding: 0px 12px; border: none; background: rgb(255, 255, 255); font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: box-shadow 220ms, color 220ms; {{ $current ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 2px inset; color: rgb(32, 32, 32);' : 'box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; color: rgb(102, 102, 102);' }}"><span>{{ $page }}</span></a>
                        @endforeach
                    </nav>
                @endif
            @endif
        </div>
    </section>
@endsection
