@extends('storefront.layouts.app')

{{-- Furnivo product page — markup and styles from the design (site.zip), filled from the store. --}}
@section('furnivo', true)
@section('title', $product->seo_title ?: $product->name)
@section('meta_description', $product->seo_description ?: \Illuminate\Support\Str::limit(\App\Support\RichText::text($product->short_description ?: $product->description), 160))
@section('og_type', 'product')

@php
    $variant = $selected;
    $brand = $product->brandRecord;
    $parent = $category?->parent ?? $category;
    $pad = 'padding: 0px clamp(24px, 4.5vw, 80px)';
    // Gallery: this piece's photos, then others from the same category (as in the design) — 4 thumbnails.
    $sameCategory = $parent ? app(\App\Services\Catalog\StorefrontCatalog::class)->inCategory(app(\App\Services\Catalog\StorefrontCatalog::class)->products(), $parent)
        ->where('products.id', '!=', $product->id)->with(['brandRecord'])->limit(8)->get() : collect();
    $gallery = $product->media->concat($sameCategory->pluck('media')->flatten())->unique('id')->take(4)->values();
    $reviews = $product->reviews()->approved()->latest('id')->get();   // newest submitted first
    $avg = $reviews->count() ? round($reviews->avg('rating'), 1) : (float) $product->rating_average;
    $bars = collect([5, 4, 3, 2, 1])->map(fn ($s) => ['star' => $s, 'count' => $n = $reviews->where('rating', $s)->count(), 'pct' => $reviews->count() ? round($n / $reviews->count() * 100) : 0]);
    $starText = fn ($r) => str_repeat('★', (int) round($r)).str_repeat('☆', 5 - (int) round($r));
    $bundle = collect([$product])->concat($sameCategory->take(3));
    $recent = \App\Models\Product::query()->whereIn('id', array_diff((array) session('recently_viewed', []), [$product->id]))->pluck('id')->all();
    $catalog = app(\App\Services\Catalog\StorefrontCatalog::class);
    $recentProducts = $recent ? $catalog->products()->with('brandRecord')->whereIn('products.id', $recent)->get()->sortBy(fn ($p) => array_search($p->id, $recent))->values()->take(4) : collect();
    // As in the design: fewer than 4 viewed → fill up from the start of the catalogue.
    if ($recentProducts->count() < 4) {
        $recentProducts = $recentProducts->concat($catalog->products()->with('brandRecord')->whereNotIn('products.id', $recentProducts->pluck('id')->push($product->id))
            ->reorder()->orderBy('products.id')->limit(4 - $recentProducts->count())->get())->values();
    }
    $customOrder = store_route('custom-order');
    // $config from the controller holds the product's real options and variants.
    $picker = $config;
    $hasOptions = collect($picker['options'] ?? [])->isNotEmpty();
    // The design's extra "configure your piece" choices (finish, dimensions…) are separate.
    $config = collect((array) $product->configuration);
    $label = 'font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102);';

    // Texts from the product itself, else the store defaults (Online Store → Preferences).
    $rich = \App\Support\RichText::class;
    $defaults = fn (string $key) => settings()->trans('product_page.'.$key);
    $shortText = $product->short_description ?: $defaults('short_description');
    $sections = collect([
        'desc' => [__('store.theme.pdp_description'), $product->description ?: $product->short_description],
        'dims' => [__('store.theme.pdp_dimensions'), $product->dimensions ?: $defaults('dimensions')],
        'deliv' => [__('store.theme.pdp_delivery'), $product->delivery_returns ?: $defaults('delivery_returns')],
    ])->filter(fn ($section) => $rich::text($section[1]) !== '');

    // The reassurances under the bag button, one per line: the product's own, else the theme's
    // (Online Store → Customize).
    $badges = collect(preg_split('/\r?\n/', (string) ($product->trust_badges ?: settings()->trans('theme.trust_badges'))))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->take(4)
        ->values()
        ->all();

    $badges = $badges ?: ['6–12 week lead time', 'White-glove UK delivery', '5-year warranty'];
@endphp

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->product($product)) !!}</script>
    {{-- The path under the search result: Home › Category › Product. The service adds
         Home itself, and each item is ['label' => …, 'url' => …]. --}}
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->breadcrumbs(array_values(array_filter([
        $product->categories->first() ? ['label' => $product->categories->first()->name, 'url' => store_route('collections.show', ['slug' => $product->categories->first()->slug])] : null,
        ['label' => $product->name, 'url' => store_route('products.show', ['slug' => $product->slug])],
    ])))) !!}</script>
@endpush

@section('content')
    <section style="width: 100%; padding: clamp(18px, 2vw, 28px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 90px);">
        {{-- Breadcrumb --}}
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; margin-bottom: 30px;">
            <a href="{{ store_route('home') }}" class="scp7" style="color: rgb(102, 102, 102); cursor: pointer; transition: color 200ms;"><span>{{ __('store.theme.home') }}</span></a>
            @if ($parent)
                <span style="color: rgb(201, 197, 191);">/</span>
                <a href="{{ store_route('collections.show', ['slug' => $parent->slug]) }}" class="scp7" style="color: rgb(102, 102, 102); cursor: pointer; transition: color 200ms;"><span>{{ $parent->name }}</span></a>
            @endif
            <span style="color: rgb(201, 197, 191);">/</span>
            <span class="scp7" aria-current="page" style="color: rgb(32, 32, 32); cursor: default; transition: color 200ms;"><span>{{ $product->name }}</span></span>
        </nav>

        <div class="pdp-cols fx-pdp-layout" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: clamp(36px, 5vw, 72px); align-items: start;">
            {{-- Gallery --}}
            <div class="pdp-gallery" style="display: grid; grid-template-columns: 84px 1fr; gap: 16px; align-items: start;"
                 x-data="{ current: 0, zoom: false, spin: false, copied: false,
                           async share() { const url = window.location.href; if (navigator.share) { try { await navigator.share({ title: @js($product->name), url }); return } catch (e) { if (e.name === 'AbortError') return } } try { if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(url) } else { const box = document.createElement('textarea'); box.value = url; box.style.position = 'fixed'; box.style.opacity = '0'; document.body.appendChild(box); box.select(); document.execCommand('copy'); box.remove() } this.copied = true; setTimeout(() => this.copied = false, 2200) } catch (e) { window.prompt(@js(__('store.product.copy_link')), url) } } }"
                 @keydown.escape.window="zoom = false">
                <div class="pdp-thumbs" style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($gallery as $i => $media)
                        <button type="button" @click="current = {{ $i }}" aria-label="Show image {{ $i + 1 }}" style="position: relative; aspect-ratio: 1 / 1; overflow: hidden; background: rgb(239, 236, 231); cursor: pointer; transition: box-shadow 220ms; border: none; padding: 0px; display: block; width: 100%;"
                                :style="{ boxShadow: current === {{ $i }} ? 'rgb(29, 29, 29) 0px 0px 0px 2px inset' : 'rgb(228, 225, 218) 0px 0px 0px 1px inset' }">
                            <img alt="" src="{{ $media->url('thumb') }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 500ms cubic-bezier(0.22, 1, 0.36, 1), opacity 300ms;"
                                 :style="{ transform: current === {{ $i }} ? 'scale(1.06)' : 'scale(1)', opacity: current === {{ $i }} ? 1 : 0.62 }">
                        </button>
                    @endforeach
                </div>
                <div style="position: relative; width: 100%; aspect-ratio: 4 / 5; overflow: hidden; background: rgb(239, 236, 231);">
                    @foreach ($gallery as $i => $media)
                        <img alt="{{ $i === 0 ? $product->name : '' }}" src="{{ $media->url('large') }}" srcset="{{ $media->srcset() }}" sizes="(max-width: 980px) 100vw, 50vw" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                             x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif :class="spin && 'fv-spin'"
                             style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: cover;">
                    @endforeach
                    <button type="button" @click="zoom = true" title="{{ __('store.theme.expand') }}" aria-label="{{ __('store.theme.expand_image') }}" style="position: absolute; top: 16px; left: 16px; width: 42px; height: 42px; border: none; background: rgba(255, 255, 255, 0.92); color: rgb(29, 29, 29); cursor: pointer; z-index: 3; display: flex; align-items: center; justify-content: center;">⤢</button>
                    <div style="position: absolute; top: 16px; right: 16px; display: flex; gap: 10px; z-index: 3;">
                        <button type="button" @click="share()" :title="copied ? 'Link copied' : 'Share'" aria-label="{{ __('store.theme.share') }}" style="width: 42px; height: 42px; border: none; background: rgba(255, 255, 255, 0.92); color: rgb(32, 32, 32); cursor: pointer; display: flex; align-items: center; justify-content: center;"><svg fill="none" height="18" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" width="18" aria-hidden="true"><circle cx="18" cy="5" r="2.6"></circle><circle cx="6" cy="12" r="2.6"></circle><circle cx="18" cy="19" r="2.6"></circle><path d="M8.3 10.7l7.4-4.3M8.3 13.3l7.4 4.3"></path></svg></button>
                        @php $saved = app(\App\Services\Wishlist\WishlistService::class)->has($product->id); @endphp
                        <form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $product->id]) }}" x-data="wishlistButton({{ $saved ? 'true' : 'false' }})" @submit.prevent="toggle($event)" style="margin: 0px;">
                            @csrf
                            <button type="submit" :aria-pressed="saved" title="{{ __('store.theme.wishlist') }}" aria-label="{{ __('store.theme.add_to_wishlist') }}" style="width: 42px; height: 42px; border: none; background: rgba(255, 255, 255, 0.92); color: rgb(32, 32, 32); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px;"><span x-text="saved ? '♥' : '♡'">{{ $saved ? '♥' : '♡' }}</span></button>
                        </form>

                {{-- Out of stock: take an email instead of losing the visitor. --}}
                @unless ($variant->isInStock())
                    <form method="POST" action="{{ store_route('stock-alerts.store') }}"
                          style="margin-top: 18px; border: 1px solid rgb(236, 236, 236); padding: 20px;">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                        <p style="margin: 0px; font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(199, 64, 52);">{{ __('store.stock_alert.title') }}</p>
                        <p style="margin: 8px 0px 14px; font-size: 14px; color: rgb(102, 102, 102);">{{ __('store.stock_alert.intro') }}</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <label for="stock-alert-email" class="sr-only-fv">{{ __('store.stock_alert.email') }}</label>
                            <input id="stock-alert-email" type="email" name="email" required
                                   value="{{ auth('web')->user()?->email }}"
                                   placeholder="{{ __('store.stock_alert.email') }}"
                                   style="flex: 1 1 200px; min-width: 0px; height: 48px; padding: 0px 14px; border: 1px solid rgb(217, 213, 207); font-size: 14px;">
                            <button type="submit" class="scpb" style="height: 48px; padding: 0px 22px; border: none; background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.stock_alert.submit') }}</button>
                        </div>
                        {{-- The shared reCAPTCHA partial adds its token to every POST form. --}}
                        @error('email')<p role="alert" style="margin: 10px 0px 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
                    </form>
                @endunless
                    </div>
                    <button type="button" @click="spin = ! spin" title="View in 3D" aria-label="View in 3D" :aria-pressed="spin" style="position: absolute; bottom: 16px; right: 16px; width: 52px; height: 52px; border: none; background: rgb(255, 255, 255); color: rgb(29, 29, 29); cursor: pointer; z-index: 3; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; box-shadow: rgba(0, 0, 0, 0.45) 0px 6px 18px -8px;"><span style="font-size: 13px; font-weight: 700; letter-spacing: 0.02em;">3D</span><svg fill="none" height="8" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 10" width="20" aria-hidden="true"><path d="M4 3c2-2 14-2 16 0M6 6c1.6 1.4 10.4 1.4 12 0"></path><path d="M3 3l1.5-.5M21 3l-1.5-.5"></path></svg></button>
                </div>
                {{-- Enlarged image --}}
                <div x-show="zoom" x-cloak @click.self="zoom = false" role="dialog" aria-modal="true" aria-label="{{ $product->name }}" style="position: fixed; inset: 0px; z-index: 100; background: rgba(12, 12, 12, 0.92);">
                    @foreach ($gallery as $i => $media)
                        <img x-show="current === {{ $i }}" alt="" src="{{ $media->url('large') }}" loading="lazy" style="position: absolute; inset: 5vh 5vw; width: 90vw; height: 90vh; object-fit: contain;">
                    @endforeach
                    <button type="button" @click="zoom = false" aria-label="{{ __('store.common.close') }}" style="position: absolute; top: 20px; right: 24px; width: 44px; height: 44px; border: 1px solid rgba(255, 255, 255, 0.5); background: transparent; color: rgb(255, 255, 255); font-size: 18px; cursor: pointer;">✕</button>
                </div>
            </div>

            {{-- Info --}}
            <div class="pdp-info" style="display: flex; flex-direction: column;">
                @if ($brand)
                    <a href="{{ store_route('brands.show', ['slug' => $brand->slug]) }}" style="cursor: pointer; margin-bottom: 18px; display: block;">
                        @if ($logo = $brand->logoUrl())
                            <img alt="{{ $brand->name }}" src="{{ $logo }}" style="height: 52px; width: auto; max-width: 240px; object-fit: contain; display: block;">
                        @else
                            <span style="font-size: 12px; letter-spacing: 0.24em; text-transform: uppercase; color: rgb(102, 102, 102);">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endif
                <h1 style="margin: 0px 0px 16px; font-size: clamp(34px, 3.8vw, 50px); line-height: 1.04; font-weight: 800; letter-spacing: -0.03em;"><span>{{ $product->name }}</span></h1>
                @php
                    // The reviews people can actually read on this page, and the score they add up to.
                    $reviewCount = $reviews->count();
                    $filledStars = (int) round($avg);
                @endphp
                @if ($reviewCount === 0)
                    {{-- No reviews yet: an empty star row would read as a bad score. --}}
                    <a href="#reviews" style="display: inline-flex; margin-bottom: 20px; font-size: 14px; color: rgb(102, 102, 102);">{{ __('store.product.no_reviews_yet') }}</a>
                @else
                <a href="#reviews" style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 2px; font-size: 14px; color: rgb(32, 32, 32);" aria-hidden="true">@for ($s = 1; $s <= 5; $s++)<span style="{{ $s > $filledStars ? 'color: rgb(201, 197, 191);' : '' }}">★</span>@endfor</div>
                    <span style="font-size: 14px; color: rgb(102, 102, 102);"><span>{{ number_format($avg, 1) }}</span> · {{ trans_choice('store.product.review_count', $reviewCount, ['count' => $reviewCount]) }}</span>
                </a>
                @endif
                {{-- Price, options and the bag button share one state, so they never disagree. --}}
                <div @if ($hasOptions) x-data="variantPicker(@js($picker))" x-init="initPicker()" @endif>
                <div style="display: flex; align-items: baseline; gap: 12px; padding-bottom: 24px; margin-bottom: 24px; border-bottom: 1px solid rgb(236, 236, 236);">
                    <div style="font-size: 26px; font-weight: 600; color: rgb(32, 32, 32); letter-spacing: -0.01em;"><span @if ($hasOptions) x-text="variant?.price" @endif><x-store.fv-price :money="$variant->price" style="display: inline-flex; align-items: center;" /></span></div>
                    @if ($variant->isOnSale())
                        <span style="font-size: 18px; color: rgb(139, 135, 127); text-decoration: line-through;" @if ($hasOptions) x-show="variant?.compare" @endif>
                            <span @if ($hasOptions) x-text="variant?.compare" @endif><x-store.fv-price :money="$variant->compare_at_price" style="display: inline-flex; align-items: center;" /></span>
                        </span>
                        <span style="font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(199, 64, 52);" @if ($hasOptions) x-show="variant?.save" @endif>
                            <span @if ($hasOptions) x-text="variant?.save + '% off'" @endif>{{ (int) round(100 - ($variant->price->minor / max(1, $variant->compare_at_price->minor)) * 100) }}% off</span>
                        </span>
                    @endif
                    <span style="font-size: 13px; color: rgb(139, 135, 127);">incl. VAT</span>
                </div>
                @if ($rich::text($shortText) !== '')
                    <div class="fv-rich" style="margin: 0px 0px 28px; font-size: 17px; line-height: 29px; color: rgb(85, 85, 85);">{!! $rich::render($shortText) !!}</div>
                @endif

                <form method="POST" action="{{ store_route('cart.add') }}" data-tour="store-add" x-data="{ ...addToCart(), qty: 1, picked: @js($config->mapWithKeys(fn ($o) => [$o['label'] => $o['values'][0] ?? ''])->all()) }" @submit.prevent="submit($event).then(() => error || $dispatch('open-cart-drawer'))">
                    @csrf
                    <input type="hidden" name="variant_id" @if ($hasOptions) :value="variantId" @endif value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" :value="qty">
                    @if ($hasOptions)
                        <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px;">
                            @foreach ($picker['options'] as $option)
                                <fieldset style="display: flex; flex-direction: column; gap: 10px; border: none; margin: 0px; padding: 0px; min-width: 0px;">
                                    <legend style="{{ $label }} padding: 0px; margin-bottom: 10px;">
                                        <span>{{ $option['name'] }}</span>
                                        <span style="color: rgb(32, 32, 32);" x-text="' · ' + (options.find(o => o.id === {{ $option['id'] }})?.values.find(v => v.id === chosen[{{ $option['id'] }}])?.label ?? '')"></span>
                                    </legend>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                        @foreach ($option['values'] as $value)
                                            <button type="button" class="scpa" @click="pick({{ $option['id'] }}, {{ $value['id'] }})"
                                                    :aria-pressed="isChosen({{ $option['id'] }}, {{ $value['id'] }})"
                                                    :style="{
                                                        boxShadow: isChosen({{ $option['id'] }}, {{ $value['id'] }}) ? 'rgb(29, 29, 29) 0px 0px 0px 2px inset' : 'rgb(217, 213, 207) 0px 0px 0px 1px inset',
                                                        opacity: isAvailable({{ $option['id'] }}, {{ $value['id'] }}) ? 1 : 0.4,
                                                        textDecoration: isAvailable({{ $option['id'] }}, {{ $value['id'] }}) ? 'none' : 'line-through',
                                                    }"
                                                    style="border: none; background: rgb(255, 255, 255); color: rgb(32, 32, 32); padding: 9px 15px; font-size: 14px; cursor: pointer; transition: box-shadow 200ms;">{{ $value['label'] }}</button>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach

                            <p x-show="variant && ! variant.available" x-cloak style="margin: 0px; font-size: 14px; color: rgb(199, 64, 52);">{{ __('store.product.out_of_stock') }}</p>
                            <p x-show="variant && variant.available && variant.left !== null" x-cloak x-text="variant?.left !== null ? @js(__('store.product.only_left', ['count' => ':count'])).replace(':count', variant.left) : ''" style="margin: 0px; font-size: 14px; color: rgb(139, 135, 127);"></p>
                        </div>
                    @endif

                    @if ($config->isNotEmpty())
                        <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid rgb(236, 236, 236); padding-bottom: 14px;">
                                <div style="font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; color: rgb(32, 32, 32);">{{ __('store.theme.configure_your_piece') }}</div>
                                <a href="{{ store_route('pages.show', ['slug' => 'size-guide']) }}" class="scp9" style="cursor: pointer; display: flex; align-items: center; gap: 7px; font-size: 13px; color: rgb(29, 29, 29); transition: opacity 200ms;"><svg fill="none" height="16" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18M7 3v18M17 3v18"></path></svg>{{ __('store.theme.size_guide') }}</a>
                            </div>
                            @foreach ($config as $option)
                                @php $key = $option['label']; @endphp
                                <fieldset style="display: flex; flex-direction: column; gap: 10px; border: none; margin: 0px; padding: 0px; min-width: 0px;">
                                    <input type="hidden" name="options[{{ $key }}]" :value="picked[@js($key)]">
                                    <legend style="{{ $label }} padding: 0px; margin-bottom: 10px;"><span>{{ $key }}</span><span style="color: rgb(32, 32, 32);"> · <span x-text="picked[@js($key)]">{{ $option['values'][0] ?? '' }}</span></span></legend>
                                    @if (! empty($option['swatches']))
                                        <div style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center;">
                                            @foreach ($option['values'] as $value)
                                                <button type="button" @click="picked[@js($key)] = @js($value)" title="{{ $value }}" aria-label="{{ $key }}: {{ $value }}" :aria-pressed="picked[@js($key)] === @js($value)"
                                                        style="width: 28px; height: 28px; border-radius: 50%; background: {{ $option['swatches'][$value] ?? '#ccc' }}; border: none; padding: 0px; cursor: pointer; transition: box-shadow 260ms;"
                                                        :style="{ boxShadow: picked[@js($key)] === @js($value) ? 'rgb(255, 255, 255) 0px 0px 0px 2px, rgb(29, 29, 29) 0px 0px 0px 3px' : 'rgba(0, 0, 0, 0.14) 0px 0px 0px 1px inset' }"></button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                            @foreach ($option['values'] as $value)
                                                @if (str_starts_with($value, '+ '))
                                                    <a href="{{ $customOrder }}?product={{ urlencode($product->slug) }}" x-data="{}" @click.prevent="$dispatch('open-custom-order', { product: @js($product->slug), category: @js($parent?->name ?? '') })" class="scpa" style="border: none; box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; background: rgb(255, 255, 255); color: rgb(32, 32, 32); padding: 9px 15px; font-size: 14px; cursor: pointer; transition: box-shadow 260ms;"><span>{{ $value }}</span></a>
                                                @else
                                                    <button type="button" class="scpa" @click="picked[@js($key)] = @js($value)" :aria-pressed="picked[@js($key)] === @js($value)"
                                                            style="border: none; background: rgb(255, 255, 255); color: rgb(32, 32, 32); padding: 9px 15px; font-size: 14px; cursor: pointer; transition: box-shadow 260ms;"
                                                            :style="{ boxShadow: picked[@js($key)] === @js($value) ? 'rgb(29, 29, 29) 0px 0px 0px 2px inset' : 'rgb(217, 213, 207) 0px 0px 0px 1px inset' }"><span>{{ $value }}</span></button>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </fieldset>
                            @endforeach
                        </div>
                    @endif

                    <div style="display: flex; align-items: stretch; gap: 14px; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; border: 1px solid rgb(217, 213, 207);">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('store.theme.decrease_quantity') }}" style="width: 46px; height: 56px; border: none; background: transparent; font-size: 18px; cursor: pointer; color: rgb(32, 32, 32);">–</button>
                            <span style="width: 42px; text-align: center; font-size: 16px;" aria-live="polite"><span x-text="qty">1</span></span>
                            <button type="button" @click="qty = Math.min(99, qty + 1)" aria-label="{{ __('store.theme.increase_quantity') }}" style="width: 46px; height: 56px; border: none; background: transparent; font-size: 18px; cursor: pointer; color: rgb(32, 32, 32);">+</button>
                        </div>
                        <button type="submit" class="scpb" :disabled="busy" @disabled(! $variant->isPurchasable()) style="flex: 1 1 0%; height: 56px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; transition: opacity 250ms;"><span>{{ $variant->isPurchasable() ? 'Add to cart' : __('store.product.sold_out') }}</span></button>
                    </div>
                    <p x-show="error" x-cloak x-text="error" role="alert" style="margin: 0px 0px 14px; font-size: 14px; color: rgb(199, 64, 52);"></p>
                </form>
                </div>
                <a href="{{ $customOrder }}?product={{ urlencode($product->slug) }}" x-data="{}" @click.prevent="$dispatch('open-custom-order', { product: @js($product->slug), category: @js($parent?->name ?? '') })" class="scpc" style="height: 56px; border: 1px solid rgb(29, 29, 29); background: rgb(255, 255, 255); color: rgb(29, 29, 29); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer; transition: background 250ms, color 250ms; margin-bottom: 26px; display: flex; align-items: center; justify-content: center;">Custom Order — Make it yours</a>

                <div class="fx-trust-badges" style="display: grid; grid-template-columns: repeat({{ max(1, count($badges)) }}, 1fr); gap: 16px; padding: 22px 0px; border-top: 1px solid rgb(236, 236, 236); border-bottom: 1px solid rgb(236, 236, 236); margin-bottom: 6px;">
                    @foreach ($badges as $badge)
                        <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 8px;">
                            <svg fill="none" height="22" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" width="22" style="color: rgb(32, 32, 32);" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>
                            <span style="font-size: 12px; line-height: 16px; color: rgb(102, 102, 102);">{!! nl2br(e($badge)) !!}</span>
                        </div>
                    @endforeach
                </div>

                <div x-data="{ open: @js($sections->keys()->first()) }">
                    @foreach ($sections as $key => [$title, $body])
                        <div style="border-bottom: 1px solid rgb(236, 236, 236);">
                            <button type="button" @click="open = open === '{{ $key }}' ? null : '{{ $key }}'" :aria-expanded="open === '{{ $key }}'" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 20px 0px; font-size: 15px; letter-spacing: 0.02em; color: rgb(32, 32, 32); width: 100%; border: none; background: transparent; text-align: left;">
                                <span><span>{{ $title }}</span></span>
                                <span style="font-size: 20px; line-height: 1; color: rgb(102, 102, 102);" aria-hidden="true"><span x-text="open === '{{ $key }}' ? '–' : '+'">{{ $loop->first ? '–' : '+' }}</span></span>
                            </button>
                            <div x-show="open === '{{ $key }}'" @unless ($loop->first) x-cloak @endunless>
                                <div class="fv-rich" style="padding: 0px 0px 22px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">{!! $rich::render($body) !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top: 26px; padding-top: 22px; border-top: 1px solid rgb(236, 236, 236); display: flex; align-items: center; justify-content: space-between; font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(102, 102, 102);">
                    <span>{{ __('store.theme.product_code') }}</span>
                    <span style="color: rgb(32, 32, 32);"><span>{{ $variant->sku }}</span></span>
                </div>
            </div>
        </div>
    </section>

    @include('storefront.products.bundle', ['bundle' => $bundle])
    @include('storefront.products.reviews', ['reviews' => $reviews, 'avg' => $avg, 'bars' => $bars, 'starText' => $starText])

    @if ($sameCategory->isNotEmpty())
        @include('storefront.products.rail', ['rail' => ['key' => 'related', 'title' => __('store.theme.you_may_also_like'), 'products' => $sameCategory, 'url' => $parent ? store_route('collections.show', ['slug' => $parent->slug]) : store_route('shop')]])
    @endif
    {{-- What this visitor looked at before, from their own cookie. --}}
    @if ($recentlyViewed->isNotEmpty())
        @include('storefront.products.rail', ['rail' => ['key' => 'recent', 'title' => __('store.theme.recently_viewed'), 'products' => $recentlyViewed, 'url' => store_route('shop')]])
    @endif
@endsection
