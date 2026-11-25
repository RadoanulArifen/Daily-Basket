@extends('storefront.layouts.app')

{{-- Cart page from the design (cart.html): basket lines and order summary. Quantities and
     "Remove" use the cart's own actions; delivery and the free-delivery bar follow
     Settings → Shipping; each deal and discount code is listed by name. --}}
@section('freshnest', true)
@section('title', __('Cart'))
@section('noindex', true)

@section('content')
@php
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $delivery = app(\App\Themes\Freshnest\Delivery::class);
    $goods = $summary->discountedSubtotal();
    $fee = $delivery->feeFor($goods);
    $toFree = $delivery->toFree($goods);
    $total = $fee ? $goods->add($fee) : $goods;
    $count = $summary->count;
    $itemAction = fn ($id) => rtrim(store_route('cart'), '/').'/items/'.$id;
@endphp
    @include('storefront.partials.page-band', ['title' => __('Cart'), 'blurb' => __('Review your basket, adjust quantities and check out.').($delivery->freeThreshold() ? ' Free delivery on orders over '.price_whole($delivery->freeThreshold()).'.' : '')])

    <div class="wrap" style="padding-top:clamp(28px,3.5vw,44px); padding-bottom:clamp(40px,5vw,64px); display:flex; flex-wrap:wrap; gap:24px; align-items:flex-start;">
      <div style="flex:2 1 520px; min-width:0; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:clamp(18px,2.4vw,28px); display:flex; flex-direction:column; gap:4px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:14px; border-bottom:1px solid var(--fn-line);">
          <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:18px; color:var(--fn-ink);">{{ __('Your basket') }}</strong>
          <span style="font-size:14px; color:var(--fn-muted);">{{ $count }} {{ $count === 1 ? __('item') : __('items') }}</span>
        </div>
        @foreach ($summary->notices as $notice)
          <p role="status" style="margin:12px 0 0; font-size:13.5px; color:#D4380D;">{{ $notice }}</p>
        @endforeach
        @if (empty($summary->lines))
          <div style="padding:40px 0; display:flex; flex-direction:column; align-items:flex-start; gap:14px;">
            <span style="font-size:15px; color:var(--fn-muted);">{{ __('Your basket is empty.') }}</span>
            <a href="{{ store_route('shop') }}" style="display:inline-flex; align-items:center; padding:12px 22px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15px;">{{ __('Start shopping') }}</a>
          </div>
        @endif
        @foreach ($summary->lines as $line)
          @php
              $id = $line->item->id; $qty = $line->item->quantity;
              $media = $line->variant->image ?? $line->product->media->first();
              $atMax = $line->available !== null && $qty >= $line->available;
              $size = $line->variant->title();
          @endphp
          <div style="display:flex; align-items:center; flex-wrap:wrap; gap:16px; padding:16px 0; border-bottom:1px solid #F2F6F2;">
            @if ($media)<img loading="lazy" src="{{ $media->url('thumb') }}" alt="{{ $line->product->name }}" style="width:72px; height:72px; border-radius:14px; object-fit:cover; background:var(--fn-primary-tint); flex:0 0 auto;" />@else<span style="width:72px; height:72px; border-radius:14px; background:var(--fn-primary-tint); flex:0 0 auto;"></span>@endif
            <div style="flex:1 1 180px; min-width:0; display:flex; flex-direction:column; gap:4px;">
              <a href="{{ store_route('products.show', ['slug' => $line->product->slug]) }}" style="font-weight:700; font-size:15.5px; color:var(--fn-ink);">{{ $line->product->name }}</a>
              <span style="font-size:13.5px; color:var(--fn-muted);">{{ $size ? $size.' · ' : '' }}{{ $money($line->unitPrice) }}</span>
              @if ($options = $line->item->optionsLabel())<span style="font-size:12.5px; color:var(--fn-muted);">{{ $options }}</span>@endif
            </div>
            <div style="display:flex; align-items:center; border:1.5px solid var(--fn-primary-tint); border-radius:999px; overflow:hidden;">
              <form method="POST" action="{{ $itemAction($id) }}" style="margin:0;">@csrf
                @if ($qty > 1)@method('PATCH')<input type="hidden" name="quantity" value="{{ $qty - 1 }}">@else @method('DELETE')@endif
                <button type="submit" aria-label="{{ __('Decrease') }}" style="width:38px; height:38px; border:0; background:var(--fn-canvas); font-size:18px; color:var(--fn-ink); cursor:pointer;">−</button>
              </form>
              <span style="min-width:34px; text-align:center; font-weight:700; font-size:15px; color:var(--fn-ink);">{{ $qty }}</span>
              <form method="POST" action="{{ $itemAction($id) }}" style="margin:0;">@csrf @method('PATCH')<input type="hidden" name="quantity" value="{{ $qty + 1 }}">
                <button type="submit" aria-label="{{ __('Increase') }}" @disabled($atMax) style="width:38px; height:38px; border:0; background:var(--fn-canvas); font-size:18px; color:var(--fn-ink); cursor:pointer;{{ $atMax ? ' opacity:.35; cursor:default;' : '' }}">+</button>
              </form>
            </div>
            <strong style="min-width:70px; text-align:right; font-size:15.5px; color:var(--fn-ink);">{{ $money($line->lineTotal) }}</strong>
            <form method="POST" action="{{ $itemAction($id) }}" style="margin:0;">@csrf @method('DELETE')
              <button type="submit" style="border:0; background:transparent; color:var(--fn-muted); font-size:13.5px; text-decoration:underline; cursor:pointer;">{{ __('Remove') }}</button>
            </form>
          </div>
        @endforeach
      </div>
      <aside aria-label="{{ __('Order summary') }}" style="flex:1 1 300px; min-width:0; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:18px; padding:clamp(18px,2.4vw,28px); display:flex; flex-direction:column; gap:14px;">
        <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:18px; color:var(--fn-ink);">{{ __('Order summary') }}</strong>
        @if ($delivery->freeThreshold())
        <div style="display:flex; flex-direction:column; gap:8px;">
          <span style="font-size:13.5px; color:var(--fn-primary-dark);">{{ $toFree ? __('Add :amount more for free delivery', ['amount' => $money($toFree)]) : __("You've unlocked free delivery") }}</span>
          <div style="height:8px; border-radius:4px; background:var(--fn-primary-tint); overflow:hidden;"><div style="height:100%; width:{{ $delivery->freePercent($goods) }}%; background:var(--fn-primary);"></div></div>
        </div>
        @endif
        <div style="display:flex; justify-content:space-between; font-size:15px; color:var(--fn-muted);"><span>{{ __('Subtotal') }}</span><span style="color:var(--fn-ink);">{{ $money($summary->subtotal) }}</span></div>
        @include('storefront.partials.savings-rows', ['summary' => $summary, 'size' => 15])
        <div style="display:flex; justify-content:space-between; font-size:15px; color:var(--fn-muted);"><span>{{ __('Delivery') }}</span><span style="color:var(--fn-ink);">{{ $fee === null ? __('At checkout') : ($fee->isZero() ? __('Free') : $money($fee)) }}</span></div>
        <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:800; color:var(--fn-ink); padding-top:14px; border-top:1px solid var(--fn-line);"><span>{{ __('Total') }}</span><span>{{ $money($total) }}</span></div>
        @if (! empty($summary->lines))
          <a class="fh-ed87d8" data-tour="store-checkout" href="{{ store_route('checkout') }}" style="padding:15px 20px; border:0; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15.5px; cursor:pointer; text-align:center;">{{ __('Checkout') }}</a>
        @else
          <span aria-disabled="true" style="padding:15px 20px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15.5px; text-align:center; opacity:.5;">{{ __('Checkout') }}</span>
        @endif
        <span style="font-size:13.5px; color:var(--fn-primary); min-height:18px;"></span>
      </aside>
    </div>
@endsection
