@extends('storefront.layouts.app')

{{--
    Product page from the design (product.html). Where things come from:
      pack sizes          the product's variants (Catalog → Products), each with price, sale price, stock, SKU
      "Grown by", Origin  the product's brand (Catalog → Brands: name and origin)
      coupon box          live discount codes (Marketing → Discounts); applying one puts it on the cart
      two small cards     the product's "Trust badges" (one per line, "Title|Text"), else the theme's
      Offers on this item deals on this product (Marketing → Deals) and a spend-and-save code
      Description         the product's description; a heading starting "Storage" and the list after
                          it become the green "Storage & use" box
      Nutrition           the product's "Dimensions & Materials" field, as a list: "Energy — 160 kcal";
                          a first line like "Per 100 g" becomes the table heading. Empty = no tab.
      Reviews             approved reviews (Catalog → Reviews); signed-in customers can write one
--}}
@section('freshnest', true)
@section('title', $product->seo_title ?: $product->name)
@section('meta_description', $product->seo_description ?: \App\Support\RichText::text($product->short_description ?: $product->description))
@section('og_type', 'product')
@push('meta')
    @if ($ogImage = $product->media->first())<meta property="og:image" content="{{ $ogImage->url('large') }}">@endif
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->product($product, $category)) !!}</script>
@endpush

@section('content')
@php
    $home = app(\App\Themes\Freshnest\HomeData::class);
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $product->loadMissing(['brandRecord', 'variants.optionValues', 'options', 'media', 'categories']);
    $grower = $product->brandRecord;
    $catHref = $category ? store_route('collections.show', ['slug' => $category->slug]) : store_route('shop');
    $gallery = $product->media->isEmpty() ? collect([null]) : $product->media->values();
    $optionLabel = $product->options->pluck('name')->filter()->implode(' / ') ?: __('Pack size');

    $variantModels = $product->variants->where('is_active', true)->sortBy('position')->values();
    $variants = $variantModels->map(function ($v) use ($money) {
        $old = $v->isOnSale() ? $v->compare_at_price : null;
        return [
            'id' => $v->id, 'label' => $v->title() ?: $v->sku ?: __('Standard'), 'price' => $money($v->price), 'minor' => $v->price->minor,
            'old' => $old ? $money($old) : '', 'save' => $old ? $money($old->subtract($v->price)) : '', 'pct' => $old ? (int) round(100 - $v->price->minor / $old->minor * 100) : 0,
            'stock' => $v->track_inventory && ! $v->allow_backorder ? max(0, (int) $v->stock_quantity) : null, 'available' => $v->isInStock(), 'sku' => $v->sku,
        ];
    });
    $firstIndex = max(0, (int) $variantModels->search(fn ($v) => $v->id === $selected->id));
    $firstModel = $variantModels->get($firstIndex) ?? $selected;
    $first = ['id' => $firstModel->id, 'price' => $firstModel->price, 'old' => $firstModel->isOnSale() ? $firstModel->compare_at_price : null, 'sku' => $firstModel->sku,
        'pct' => $firstModel->isOnSale() ? (int) round(100 - $firstModel->price->minor / $firstModel->compare_at_price->minor * 100) : 0];
    $pdpConfig = ['variants' => $variants->all(), 'start' => $firstIndex, 'unit' => str_replace('9.99', '__', $money(\App\Support\Money::of('9.99', $firstModel->price->currency)))];
    $wished = app(\App\Services\Wishlist\WishlistService::class)->has($product->id);

    $summary = app(\App\Services\Cart\CartService::class)->summary();
    $appliedCode = $summary->discount?->code;
    $cartTotal = $summary->subtotal;
    $codes = \App\Models\Discount::query()->where('is_active', true)
        ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
        ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
        ->orderBy('id')->limit(3)->get();
    $spend = $home->spendDiscount();

    $freeAt = app(\App\Themes\Freshnest\Delivery::class)->freeThreshold();
    $badgeLines = array_filter(array_map('trim', preg_split('/\R/', (string) $product->trust_badges)));
    $badges = collect($badgeLines ?: [__('45-min delivery').'|'.($freeAt ? __('Free over :amount', ['amount' => price_whole($freeAt)]) : __('Same day')), 'Freshness promise|Refund within 24 h'])
        ->map(fn ($l) => array_map('trim', explode('|', $l, 2)))->take(2)->values()->all();
    $badgeIcons = ['<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 7h10.5v9H2.5"></path><path d="M13 10h4.2l3.3 3.3V16H13"></path><circle cx="6.5" cy="17" r="1.8"></circle><circle cx="17" cy="17" r="1.8"></circle></svg>', '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7"></path><path d="M4 4v4h4"></path></svg>'];

    // Deals on this product, largest saving first.
    $unit = $firstModel->price;
    $deals = $home->promotions()->filter(fn ($p) => match ($p->applies_to) {
            'products' => in_array($product->id, array_map('intval', (array) $p->product_ids), true),
            'category' => $product->categories->contains('id', $p->category_id),
            default => true,
        })->map(function ($p) use ($unit, $product, $category) {
            $qty = $p->buy_quantity + $p->get_quantity;
            $total = $unit->multiply($p->buy_quantity)->add($unit->percentage(100 - $p->get_percent)->multiply($p->get_quantity));
            $save = (int) round((1 - $total->minor / max(1, $unit->multiply($qty)->minor)) * 100);
            $text = $p->applies_to === 'products'
                ? __('Add :qty :name and the deal is applied automatically at checkout.', ['qty' => $qty, 'name' => $product->name])
                : __('Mix any ').$qty.' items from '.($p->category?->name ?? 'the shop').'. Applied automatically at checkout.';
            return ['promotion' => $p, 'qty' => $qty, 'total' => $total, 'save' => $save, 'text' => $text];
        })->values()->take(2);

    // Description: the part from a "Storage…" heading on becomes the green box.
    $desc = (string) $product->description;
    $descMain = $desc; $tips = []; $tipsTitle = __('Storage & use');
    if (preg_match('#<h[23][^>]*>\s*(Storage[^<]*)</h[23]>(.*)$#is', $desc, $m)) {
        $descMain = substr($desc, 0, strpos($desc, $m[0]));
        $tipsTitle = html_entity_decode(strip_tags($m[1]));
        preg_match_all('#<li[^>]*>(.*?)</li>#is', $m[2], $li);
        $tips = array_map(fn ($t) => trim(html_entity_decode(strip_tags($t))), $li[1]);
    }

    // Nutrition: list items "Energy — 160 kcal" (or "Energy: 160 kcal").
    $dims = (string) $product->dimensions;
    preg_match_all('#<li[^>]*>(.*?)</li>#is', $dims, $li);
    $nutrition = collect($li[1])->map(fn ($t) => trim(html_entity_decode(strip_tags($t))))
        ->map(fn ($t) => preg_split('/\s*(?:—|–|:)\s*/u', $t, 2))->filter(fn ($p) => count($p) === 2)->values()->all();
    $nutritionPer = preg_match('#<p[^>]*>(.*?)</p>#is', $dims, $pm) ? trim(strip_tags($pm[1])) : __('Per 100 g');

    $approved = $product->reviews()->where('status', 'approved')->latest()->get();
    $reviews = $approved->take(6);
    $bars = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => $approved->count() ? (int) round($approved->where('rating', $s)->count() / $approved->count() * 100) : 0])->all();
    $verifiedPct = $approved->count() ? (int) round($approved->where('verified_purchase', true)->count() / $approved->count() * 100) : 0;
    // "You may also like": the same aisle first, filled up to four from the rest of the shop.
    $related = $related->concat($home->products()->reject(fn ($p) => $p->id === $product->id))->unique('id')->take(4)->values();
    $tabs = array_filter(['desc' => __('Description'), 'nutri' => $nutrition ? __('Nutrition') : null, 'rev' => __('Reviews (:count)', ['count' => $product->rating_count])]);
@endphp
<div style="background:var(--fn-primary-tint); border-bottom:1px solid var(--fn-line);">
    <div class="wrap" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; font-size:13px; color:var(--fn-muted); padding-top:16px; padding-bottom:16px;">
      <a href="{{ store_route('home') }}">{{ __('Home') }}</a><span>/</span>
      <a href="{{ store_route('shop') }}">{{ __('Shop') }}</a><span>/</span>
      @if ($category)<a href="{{ $catHref }}">{{ $category->name }}</a><span>/</span>@endif
      <span style="color:var(--fn-ink); font-weight:600;" aria-current="page">{{ $product->name }}</span>
    </div>
  </div>

  <section class="wrap pdp-grid" x-data="fnProduct(@js($pdpConfig))" style="padding-top:clamp(24px,3.5vw,48px);">
    <div style="display:flex; flex-direction:column; gap:14px; min-width:0;">
      <div style="position:relative; aspect-ratio:1/1; border-radius:24px; overflow:hidden; background:var(--fn-primary-tint); border:1px solid #E6F0E7;">
        @foreach ($gallery as $i => $media)
          <div x-show="image === {{ $i }}" @if ($i) x-cloak @endif>@include('storefront.home.partials.img', ['media' => $media, 'alt' => $product->name, 'size' => 'large', 'sizes' => '(min-width: 1000px) 50vw, 100vw', 'loading' => $i ? 'lazy' : 'eager'])</div>
        @endforeach
        <span style="position:absolute; left:16px; top:16px; background:var(--fn-accent-light); color:var(--fn-ink); font-size:13px; font-weight:700; padding:7px 12px; border-radius:999px;" x-show="current.pct" x-text="current.pct + @js(__('% OFF'))">{{ $first['pct'] ? $first['pct'].'% OFF' : '' }}</span>
        <span style="position:absolute; left:16px; bottom:16px; display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.95); color:var(--fn-primary-dark); font-size:12.5px; font-weight:700; padding:7px 12px; border-radius:999px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="var(--fn-primary)"></path><path d="M3 21C8 16 13 11 21 3" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round"></path></svg>{{ __('Certified Organic') }}</span>
      </div>
      @if ($gallery->count() > 1)
      <div style="display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px;">
        @foreach ($gallery as $i => $media)
          <button type="button" @click="image = {{ $i }}" aria-label="Show image {{ $i + 1 }}" :style="{ borderColor: image === {{ $i }} ? 'var(--fn-primary)' : 'transparent' }" style="position:relative; aspect-ratio:1/1; padding:0; border-radius:16px; overflow:hidden; border:2px solid {{ $i ? 'transparent' : 'var(--fn-primary)' }}; background:var(--fn-primary-tint); cursor:pointer;">
            <span :style="{ opacity: image === {{ $i }} ? 1 : .75 }" style="position:absolute; inset:0; opacity:{{ $i ? '.75' : '1' }}; pointer-events:none;">@include('storefront.home.partials.img', ['media' => $media, 'alt' => '', 'size' => 'thumb', 'sizes' => '120px'])</span>
          </button>
        @endforeach
      </div>
      @endif
    </div>

    <div style="display:flex; flex-direction:column; gap:18px; min-width:0;">
      <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <span style="background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-size:12px; font-weight:700; padding:6px 12px; border-radius:999px;">{{ __('Organic') }}</span>
        <span style="background:var(--fn-accent-tint); color:var(--fn-accent-dark); font-size:12px; font-weight:700; padding:6px 12px; border-radius:999px;" x-text="current.available ? (current.stock !== null ? @js(__('In stock · :count left')).replace(':count', current.stock) : @js(__('In stock'))) : @js(__('Out of stock'))">{{ __('In stock') }}</span>
      </div>
      <div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(28px,3.4vw,42px); line-height:1.1; letter-spacing:-.025em; margin:0; color:var(--fn-ink);">{{ $product->name }}</h1>
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:12px; font-size:14px; color:var(--fn-muted);">
          @if ($product->rating_count)
          <span style="color:var(--fn-accent); letter-spacing:2px;">★★★★★</span>
          <span><strong style="color:var(--fn-ink);">{{ number_format((float) $product->rating_average, 1) }}</strong> · {{ number_format($product->rating_count) }} {{ $product->rating_count === 1 ? __('review') : __('reviews') }}</span>
          @endif
          @if ($product->rating_count && $grower)<span style="width:4px; height:4px; border-radius:50%; background:#C7DECB;"></span>@endif
          @if ($grower)<span>{{ __('Grown by') }} <a href="{{ store_route('brands.show', ['slug' => $grower->slug]) }}" style="color:inherit;">{{ $grower->name }}</a></span>@endif
        </div>
      </div>
      <div style="display:flex; align-items:baseline; gap:12px; flex-wrap:wrap;">
        <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:36px; color:var(--fn-ink); letter-spacing:-.02em;" x-text="current.price">{{ $money($first['price']) }}</span>
        <span style="font-size:18px; color:var(--fn-muted); text-decoration:line-through;" x-text="current.old">{{ $first['old'] ? $money($first['old']) : '' }}</span>
        <span x-show="current.save" @if (! $first['old']) x-cloak @endif><span style="font-size:13px; font-weight:700; color:var(--fn-ink); background:var(--fn-accent-light); padding:5px 10px; border-radius:8px;">{{ __('You save') }} <span x-text="current.save">{{ $first['old'] ? $money($first['old']->subtract($first['price'])) : '' }}</span></span></span>
      </div>
      <p style="margin:0; font-size:15.5px; line-height:1.7; color:var(--fn-muted); max-width:560px; text-wrap:pretty;">{{ \App\Support\RichText::text($product->short_description) }}</p>

      @if ($variants->count() > 1)
      <div data-tour="store-options">
        <span style="display:block; font-size:13.5px; font-weight:700; color:var(--fn-ink); margin-bottom:10px;">{{ $optionLabel }}</span>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
          @foreach ($variants as $i => $v)
            <button type="button" @click="pick({{ $i }})" :aria-pressed="vi === {{ $i }}" :style="vi === {{ $i }} ? { borderColor: 'var(--fn-primary)', background: 'var(--fn-primary-tint)' } : { borderColor: 'var(--fn-line)', background: '#FFFFFF' }" style="display:flex; flex-direction:column; align-items:flex-start; gap:2px; border:1.5px solid {{ $i === $firstIndex ? 'var(--fn-primary)' : 'var(--fn-line)' }}; background:{{ $i === $firstIndex ? 'var(--fn-primary-tint)' : '#FFFFFF' }}; border-radius:14px; padding:10px 16px; cursor:pointer; text-align:left;{{ $v['available'] ? '' : ' opacity:.5;' }}">
              <span style="font-size:14px; font-weight:700; color:var(--fn-ink);">{{ $v['label'] }}</span>
              <span style="font-size:12.5px; color:var(--fn-muted);">{{ $v['price'] }}</span>
            </button>
          @endforeach
        </div>
      </div>
      @endif

      <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:stretch;">
        <form method="POST" action="{{ store_route('cart.add') }}" data-tour="store-add" x-data="addToCart" @submit.prevent="submit($event).then(() => $dispatch('open-cart-drawer'))" style="display:contents;">
        @csrf
        <input type="hidden" name="variant_id" :value="current.id" value="{{ $first['id'] }}">
        <input type="hidden" name="quantity" :value="qty" value="1">
        <div style="display:flex; align-items:center; border:1.5px solid var(--fn-primary-pale); border-radius:14px; background:#FFFFFF;">
          <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('Decrease') }}" style="width:48px; height:52px; border:0; background:transparent; font-size:20px; color:var(--fn-primary-dark); cursor:pointer;">−</button>
          <span style="min-width:36px; text-align:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:17px;" x-text="qty">1</span>
          <button type="button" @click="qty = Math.min(current.stock ?? 99, qty + 1)" aria-label="{{ __('Increase') }}" style="width:48px; height:52px; border:0; background:transparent; font-size:20px; color:var(--fn-primary-dark); cursor:pointer;">+</button>
        </div>
        <button class="fh-92c14d" type="submit" :disabled="busy || ! current.available" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:6px 6px 6px 18px; flex:1 1 200px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:16px; min-height:54px; border-radius:999px; cursor:pointer; box-shadow:0 12px 26px rgba(35,99,63,.28);"><span style="white-space:nowrap;" x-text="current.available ? @js(__('Add to Cart')) + ' · ' + total() : @js(__('Out of stock'))">{{ __('Add to Cart ·') }} {{ $money($first['price']) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
        </form>
        <form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $product->id]) }}" x-data="wishlistButton({{ $wished ? 'true' : 'false' }})" @submit.prevent="toggle($event)" style="display:contents;">@csrf
        <button type="submit" :aria-pressed="saved" aria-label="{{ __('Add to wishlist') }}" style="width:54px; min-height:54px; border-radius:14px; border:1.5px solid var(--fn-primary-pale); background:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $wished ? 'var(--fn-accent)' : 'none' }}" :fill="saved ? 'var(--fn-accent)' : 'none'" aria-hidden="true"><path d="M12 20s-7.5-4.6-7.5-9.4A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z" stroke="{{ $wished ? 'var(--fn-accent)' : 'var(--fn-muted)' }}" :stroke="saved ? 'var(--fn-accent)' : 'var(--fn-muted)'" stroke-width="1.7" stroke-linejoin="round"></path></svg>
        </button>
        </form>
      </div>
      <span style="font-size:13px; color:var(--fn-primary); min-height:16px; margin-top:-8px;"></span>

      <div style="position:relative; border:2px dashed #F3C7A8; background:linear-gradient(135deg,var(--fn-accent-tint) 0%, #FFFFFF 100%); border-radius:18px; padding:16px 18px; display:flex; flex-direction:column; gap:12px;">
        <span style="position:absolute; left:-11px; top:50%; width:20px; height:20px; margin-top:-10px; border-radius:50%; background:var(--fn-canvas); border-right:2px dashed #F3C7A8;"></span>
        <span style="position:absolute; right:-11px; top:50%; width:20px; height:20px; margin-top:-10px; border-radius:50%; background:var(--fn-canvas); border-left:2px dashed #F3C7A8;"></span>
        <div style="display:flex; align-items:center; gap:10px;">
          <span style="display:inline-flex; width:36px; height:36px; flex:0 0 auto; border-radius:11px; background:var(--fn-accent); align-items:center; justify-content:center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6Z"></path><path d="M9.5 14.5l5-5"></path><circle cx="9.5" cy="9.5" r=".6" fill="#FFFFFF"></circle><circle cx="14.5" cy="14.5" r=".6" fill="#FFFFFF"></circle></svg></span>
          <span style="line-height:1.3;"><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; color:var(--fn-ink);">{{ __('Have a coupon?') }}</strong><span style="font-size:12.5px; color:var(--fn-muted);">{{ __('Tap a code below or type your own') }}</span></span>
        </div>
        <form method="POST" action="{{ store_route('cart.discount') }}" style="display:flex; gap:8px; background:#FFFFFF; border:1.5px solid #F3E2D3; border-radius:13px; padding:5px;">
          @csrf<input name="code" x-ref="code" value="{{ $appliedCode }}" aria-label="{{ __('Coupon code') }}" placeholder="{{ __('Enter coupon code') }}" style="flex:1 1 auto; min-width:0; border:0; outline:none; font-size:14.5px; padding:10px 10px; text-transform:uppercase; letter-spacing:.06em; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--fn-ink); background:transparent;" />
          <button class="fh-b7ed76" type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 18px; flex:0 0 auto; border:0; background:var(--fn-primary-dark); color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Apply') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary-dark)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
        </form>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          @foreach ($codes as $d)
            @php $on = $appliedCode === $d->code; @endphp
            <form method="POST" action="{{ store_route('cart.discount') }}" style="margin:0;">@csrf<input type="hidden" name="code" value="{{ $d->code }}">
            <button type="submit" style="display:inline-flex; align-items:center; gap:8px; border:1.5px dashed {{ $on ? 'var(--fn-primary)' : '#F3C7A8' }}; background:{{ $on ? 'var(--fn-primary-tint)' : '#FFFFFF' }}; border-radius:10px; padding:7px 11px; cursor:pointer;">
              <strong style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:12.5px; color:{{ $on ? 'var(--fn-primary-dark)' : 'var(--fn-accent-dark)' }};">{{ $d->code }}</strong>
              <span style="font-size:12px; color:var(--fn-muted);">{{ $d->type === 'percentage' ? (string) (float) $d->value.'% off' : ($d->type === 'fixed' ? price_whole(\App\Support\Money::of((string) $d->value, config('store.base_currency'))).' off' : 'Free delivery') }}{{ $d->min_subtotal ? ' '.price_whole($d->min_subtotal).'+' : '' }}</span>
            </button>
            </form>
          @endforeach
        </div>
        @if ($appliedCode || $errors->has('code'))
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:13.5px; font-weight:600; color:{{ $appliedCode ? 'var(--fn-primary-dark)' : '#D4380D' }}; background:{{ $appliedCode ? 'var(--fn-primary-tint)' : '#FDECEA' }}; padding:10px 12px; border-radius:10px;">
            <span>{{ $appliedCode ? '✓ '.$appliedCode.' is applied to your cart' : $errors->first('code') }}</span>
            @if ($appliedCode)
              <form method="POST" action="{{ store_route('cart.discount.remove') }}" style="margin:0;">@csrf @method('DELETE')<button type="submit" style="border:0; background:transparent; color:var(--fn-muted); font-size:12.5px; font-weight:600; cursor:pointer; text-decoration:underline;">{{ __('Remove') }}</button></form>
            @endif
          </div>
        @endif
      </div>

      @if ($badges)
      <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
        @foreach ($badges as $i => $badge)
        <div style="display:flex; gap:12px; align-items:center; background:#FFFFFF; border:1px solid #E6F0E7; border-radius:16px; padding:14px;">
          <span style="display:inline-flex; width:42px; height:42px; flex:0 0 auto; border-radius:12px; background:var(--fn-primary-tint); align-items:center; justify-content:center;">{!! $badgeIcons[$i % 2] !!}</span>
          <span style="line-height:1.35;"><strong style="display:block; font-size:14px; color:var(--fn-ink);">{{ $badge[0] }}</strong><span style="font-size:12.5px; color:var(--fn-muted);">{{ $badge[1] ?? '' }}</span></span>
        </div>
        @endforeach
      </div>
      @endif

      <div style="display:grid; grid-template-columns:auto 1fr; gap:8px 18px; font-size:13.5px; padding-top:16px; border-top:1px solid var(--fn-line);">
        <span style="color:var(--fn-muted);">{{ __('SKU') }}</span><span style="color:var(--fn-ink); font-weight:600;" x-text="current.sku || '—'">{{ $first['sku'] ?: '—' }}</span>
        @if ($category)<span style="color:var(--fn-muted);">{{ __('Category') }}</span><a href="{{ $catHref }}" style="font-weight:600;">{{ $category->name }}</a>@endif
        @if ($grower && filled($grower->origin))<span style="color:var(--fn-muted);">{{ __('Origin') }}</span><span style="color:var(--fn-ink); font-weight:600;">{{ $grower->origin }}</span>@endif
      </div>
    </div>
  </section>

  @if ($deals->isNotEmpty() || $spend)
  <section class="wrap" style="padding-top:clamp(40px,5vw,64px);">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
      <span style="display:inline-flex; width:8px; height:8px; border-radius:50%; background:var(--fn-accent);"></span>
      <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(21px,2.4vw,26px); letter-spacing:-.02em; margin:0; color:var(--fn-ink);">{{ __('Offers on this item') }}</h2>
    </div>
    <div class="offer-row">
      @if ($deals->get(0))@php $deal = $deals->get(0); @endphp
      <article style="position:relative; overflow:hidden; border-radius:24px; background:linear-gradient(135deg,var(--fn-accent) 0%, #FF9A4A 100%); color:#FFFFFF; padding:26px; display:flex; flex-direction:column; gap:14px; box-shadow:0 20px 40px rgba(138,79,19,.28);">
        <span style="position:absolute; right:-40px; top:-40px; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,.14);"></span>
        <span style="position:absolute; right:30px; bottom:-60px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,.1);"></span>
        <span style="position:relative; align-self:flex-start; background:#FFFFFF; color:var(--fn-accent-dark); font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:6px 11px; border-radius:999px;">{{ $deal['promotion']->label() }}</span>
        <div style="position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:.95; letter-spacing:-.03em;">
          <span style="display:block; font-size:clamp(30px,3.2vw,40px);">{{ __('BUY') }} {{ $deal['promotion']->buy_quantity }}</span>
          <span style="display:block; font-size:clamp(30px,3.2vw,40px); color:#FFFFFF;">{{ __('GET') }} {{ $deal['promotion']->get_quantity }} {{ $deal['promotion']->get_percent >= 100 ? 'FREE' : $deal['promotion']->get_percent.'% OFF' }}</span>
        </div>
        <p style="position:relative; margin:0; font-size:14.5px; line-height:1.55; color:#FFF3E8;">{{ $deal['text'] }}</p>
        <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="position:relative; margin:auto 0 0; align-self:flex-start;">@csrf<input type="hidden" name="variant_id" value="{{ $first['id'] }}"><input type="hidden" name="quantity" value="{{ $deal['qty'] }}"><button class="fh-41c4b2" type="submit" :disabled="busy" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 20px; position:relative; margin-top:auto; align-self:flex-start; border:0; background:var(--fn-primary-dark); color:#FFFFFF; font-weight:700; font-size:14.5px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Add') }} {{ $deal['qty'] }} {{ __('for') }} {{ $money($deal['total']) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary-dark)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button></form>
      </article>
      @endif
      @if ($spend)
      <article style="position:relative; overflow:hidden; border-radius:24px; background:radial-gradient(120% 140% at 100% 0%, var(--fn-primary) 0%, var(--fn-primary-dark) 60%, var(--fn-primary-deep) 100%); color:#FFFFFF; padding:26px; display:flex; flex-direction:column; gap:14px; box-shadow:0 20px 40px rgba(25,75,48,.25);">
        <svg width="150" height="150" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; right:-30px; bottom:-30px; opacity:.1;"><circle cx="12" cy="12" r="10" stroke="#FFFFFF" stroke-width="1.5"></circle><path d="M12 6v12M15 8.5c-.6-.9-1.7-1.5-3-1.5-1.9 0-3 1-3 2.3 0 3.2 6 1.8 6 5 0 1.4-1.3 2.4-3 2.4-1.4 0-2.6-.6-3.2-1.6" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"></path></svg>
        <span style="position:relative; align-self:flex-start; background:rgba(255,255,255,.16); color:#FFFFFF; font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:6px 11px; border-radius:999px;">{{ __('Code') }} {{ $spend->code }}</span>
        <div style="position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:1.05; letter-spacing:-.02em;">
          <span style="display:block; font-size:15px; font-weight:600; color:var(--fn-primary-pale); letter-spacing:0;">{{ __('Spend') }} {{ price_whole($spend->min_subtotal) }}{{ __(', get') }}</span>
          <span style="display:block; font-size:clamp(38px,4vw,52px); color:#FFFFFF;">{{ price_whole(\App\Support\Money::of((string) $spend->value, config('store.base_currency'))) }} {{ __('off') }}</span>
        </div>
        <div style="position:relative;">
          <div style="display:flex; justify-content:space-between; font-size:12.5px; color:var(--fn-primary-pale); margin-bottom:7px;"><span>{{ __('Cart:') }} <strong style="color:#FFFFFF;">{{ $money($cartTotal) }}</strong></span><span>{{ $spend->min_subtotal->isGreaterThan($cartTotal) ? $money($spend->min_subtotal->subtract($cartTotal)).' to go' : 'Unlocked ✓' }}</span></div>
          <div style="height:8px; border-radius:999px; background:rgba(255,255,255,.15); overflow:hidden;"><div style="height:100%; width:{{ $spend->min_subtotal->isZero() ? 100 : min(100, (int) round($cartTotal->minor / $spend->min_subtotal->minor * 100)) }}%; background:linear-gradient(90deg,var(--fn-primary-light),var(--fn-accent-light)); border-radius:999px;"></div></div>
        </div>
        <span style="position:relative; margin-top:auto; font-size:13px; color:var(--fn-primary-pale); line-height:1.5;">{{ __('Enter') }} {{ $spend->code }} {{ __('at checkout, or tap it in the coupon box above.') }}</span>
      </article>
      @endif
      @if ($deals->get(1))@php $deal = $deals->get(1); @endphp
      <article style="position:relative; overflow:hidden; border-radius:24px; background:var(--fn-primary-tint); border:1.5px solid var(--fn-primary-pale); color:var(--fn-ink); padding:26px; display:flex; flex-direction:column; gap:14px;">
        <span style="position:absolute; right:-30px; top:-30px; width:130px; height:130px; border-radius:50%; background:#D6EEDB;"></span>
        <span style="position:relative; align-self:flex-start; background:var(--fn-primary-dark); color:#FFFFFF; font-size:11.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:6px 11px; border-radius:999px;">{{ $deal['promotion']->label() }}</span>
        <div style="position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:1; letter-spacing:-.03em;">
          <span style="display:block; font-size:clamp(30px,3.2vw,40px); color:var(--fn-primary-dark);">{{ __('BUY') }} {{ $deal['qty'] }}</span>
          <span style="display:block; font-size:clamp(30px,3.2vw,40px); color:var(--fn-accent);">{{ __('SAVE') }} {{ $deal['save'] }}%</span>
        </div>
        <p style="position:relative; margin:0; font-size:14.5px; line-height:1.55; color:var(--fn-muted);">{{ $deal['text'] }}</p>
        <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="position:relative; margin:auto 0 0; align-self:flex-start;">@csrf<input type="hidden" name="variant_id" value="{{ $first['id'] }}"><input type="hidden" name="quantity" value="{{ $deal['qty'] }}"><button class="fh-f80239" type="submit" :disabled="busy" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 20px; position:relative; margin-top:auto; align-self:flex-start; border:0; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:14.5px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Add') }} {{ $deal['qty'] }} {{ __('for') }} {{ $money($deal['total']) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button></form>
      </article>
      @endif
    </div>
  </section>
@endif

  <section class="wrap" x-data="{ tab: 'desc' }" style="padding-top:clamp(40px,5vw,72px);">
    <div role="tablist" style="display:flex; gap:6px; flex-wrap:wrap; border-bottom:1px solid var(--fn-line);">
      @foreach ($tabs as $key => $label)
        <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'" :style="tab === '{{ $key }}' ? { color: 'var(--fn-primary-dark)', borderBottomColor: 'var(--fn-primary)' } : { color: 'var(--fn-muted)', borderBottomColor: 'transparent' }" style="border:0; background:transparent; padding:14px 18px; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15.5px; color:{{ $loop->first ? 'var(--fn-primary-dark)' : 'var(--fn-muted)' }}; border-bottom:3px solid {{ $loop->first ? 'var(--fn-primary)' : 'transparent' }}; margin-bottom:-1px; cursor:pointer;">{{ $label }}</button>
      @endforeach
    </div>

    <div x-show="tab === 'desc'">
      <div class="pdp-desc" style="padding-top:28px;">
        <div class="fn-rich" style="font-size:15.5px; line-height:1.8; color:var(--fn-ink); max-width:720px;">{!! \App\Support\RichText::render($descMain) !!}</div>
        @if ($tips)
        <div style="background:var(--fn-primary-tint); border-radius:20px; padding:24px;">
          <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:16px; color:var(--fn-primary-dark);">{{ $tipsTitle }}</strong>
          <div style="display:flex; flex-direction:column; gap:10px; margin-top:14px;">
            @foreach ($tips as $tip)
              <span style="display:flex; gap:10px; font-size:14px; color:var(--fn-ink); line-height:1.55;"><span style="color:var(--fn-primary); font-weight:800;">✓</span>{{ $tip }}</span>
            @endforeach
          </div>
        </div>
        @endif
      </div>
    </div>

    @if ($nutrition)
    <div x-show="tab === 'nutri'" x-cloak>
      <div style="padding-top:28px; max-width:620px;">
        <div style="background:#FFFFFF; border:1px solid #E6F0E7; border-radius:18px; overflow:hidden;">
          <div style="display:flex; justify-content:space-between; padding:14px 20px; background:var(--fn-primary-dark); color:#FFFFFF; font-size:13.5px; font-weight:700;"><span>{{ __('Nutrition') }}</span><span>{{ $nutritionPer }}</span></div>
          @foreach ($nutrition as [$k, $v])
            <div style="display:flex; justify-content:space-between; padding:13px 20px; border-top:1px solid var(--fn-line); font-size:14.5px;"><span style="color:var(--fn-muted);">{{ $k }}</span><strong style="color:var(--fn-ink);">{{ $v }}</strong></div>
          @endforeach
        </div>
      </div>
    </div>
    @endif

    <div x-show="tab === 'rev'" x-cloak>
      <div style="padding-top:28px;">
        <div class="rev-summary" style="background:var(--fn-primary-dark); color:#FFFFFF; border-radius:24px; padding:clamp(22px,3vw,32px);">
          <div style="display:flex; align-items:center; gap:16px;">
            <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:56px; line-height:.9; letter-spacing:-.04em;">{{ number_format((float) $product->rating_average, 1) }}</span>
            <span style="display:flex; flex-direction:column; gap:4px;">
              <span style="color:var(--fn-accent); font-size:16px; letter-spacing:2px;">★★★★★</span>
              <span style="font-size:13px; color:var(--fn-primary-pale);">{{ __('Average across') }} {{ number_format($product->rating_count) }} {{ $product->rating_count === 1 ? __('review') : __('reviews') }}</span>
            </span>
          </div>
          <div style="display:flex; flex-direction:column; gap:8px; min-width:0;">
            @foreach ($bars as $star => $pct)
              <div style="display:grid; grid-template-columns:28px minmax(0,1fr) 38px; align-items:center; gap:10px; font-size:12.5px; color:var(--fn-primary-pale);">
                <span>{{ $star }} ★</span>
                <span style="display:block; height:6px; border-radius:999px; background:rgba(255,255,255,.14); overflow:hidden;"><span style="display:block; height:100%; width:{{ $pct }}%; background:var(--fn-primary-light); border-radius:999px;"></span></span>
                <span style="text-align:right;">{{ $pct }}%</span>
              </div>
            @endforeach
          </div>
          <div style="display:flex; gap:24px;">
            <div><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:26px;">{{ $bars[5] + $bars[4] }}%</strong><span style="font-size:13px; color:var(--fn-primary-pale);">{{ __('rate it 4★ or more') }}</span></div>
            <div><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:26px;">{{ $verifiedPct }}%</strong><span style="font-size:13px; color:var(--fn-primary-pale);">{{ __('verified purchases') }}</span></div>
          </div>
          <a class="fh-151e18" href="{{ auth('web')->check() ? '#write' : store_route('login') }}" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 24px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:15px; border-radius:999px; white-space:nowrap;"><span style="white-space:nowrap;">{{ __('Write a review') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
        </div>
        <div class="pdp-revs" style="margin-top:20px;">
          @forelse ($reviews as $i => $rv)
            <article style="background:#FFFFFF; border:1px solid #E6F0E7; border-radius:20px; padding:22px; display:flex; flex-direction:column; gap:12px;">
              <div style="display:flex; align-items:center; gap:12px;">
                <span style="display:block; position:relative; width:46px; height:46px; border-radius:50%; overflow:hidden; flex:0 0 auto;">
                  <span style="display:flex; width:46px; height:46px; align-items:center; justify-content:center; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-weight:800; font-size:16px;">{{ mb_strtoupper(mb_substr($rv->name, 0, 1)) }}</span>
                </span>
                <span style="line-height:1.35; flex:1 1 auto; min-width:0;">
                  <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; color:var(--fn-ink);">{{ $rv->name }}</strong>
                  <span style="font-size:12.5px; color:var(--fn-muted);">{{ $rv->created_at->diffForHumans() }}</span>
                </span>
                <span style="color:var(--fn-accent); font-size:14px; letter-spacing:1.5px;" aria-label="{{ $rv->rating }} out of 5">{{ str_repeat('★', $rv->rating).str_repeat('☆', 5 - $rv->rating) }}</span>
              </div>
              <p style="margin:0; font-size:14.5px; line-height:1.7; color:var(--fn-ink);">{{ $rv->body }}</p>
              @if ($rv->verified_purchase)<span style="align-self:flex-start; font-size:11.5px; font-weight:700; color:var(--fn-primary); background:var(--fn-primary-tint); padding:5px 10px; border-radius:999px;">{{ __('✓ Verified purchase') }}</span>@endif
            </article>
          @empty
            <p style="grid-column:1 / -1; margin:0; font-size:15px; color:var(--fn-muted);">{{ __('No reviews yet — be the first to write one.') }}</p>
          @endforelse
        </div>
        @include('storefront.products.review-form')
      </div>
    </div>
  </section>

@if ($related->isNotEmpty())
  <section class="wrap fn-related" style="padding-top:clamp(40px,5vw,72px); padding-bottom:clamp(40px,5vw,72px);">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
      <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(23px,3vw,32px); letter-spacing:-.02em; margin:0; color:var(--fn-ink);">{{ __('You may also like') }}</h2>
      <a class="fh-ed87d8" href="{{ $catHref }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-ink); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px; white-space:nowrap;">
        {{ __('Show All') }}
        <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
      </a>
    </div>
    <div class="pgrid" style="margin-top:22px;">
      @foreach ($related as $item)
        @include('storefront.collections.card', ['c' => $home->card($item), 'compact' => true])
      @endforeach
    </div>
  </section>
@endif
@endsection
