{{-- Cart drawer contents — markup and styles from the design's drawer (GrocersDrawers).
     Loaded into the drawer (layouts/app) each time it opens; quantity and remove call the
     cart's own actions. Delivery and the free-delivery bar follow Settings → Shipping. --}}
@php
    $delivery = app(\App\Themes\Freshnest\Delivery::class);
    $goods = $summary->discountedSubtotal();
    $fee = $delivery->feeFor($goods);
    $toFree = $delivery->toFree($goods);
    $total = $fee ? $goods->add($fee) : $goods;
@endphp
    <div style="display:flex; align-items:center; justify-content:space-between; padding:20px 22px; background:#FFFFFF; border-bottom:1px solid var(--fn-line);">
      <strong id="cart-drawer-title" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:20px;">{{ __('Your cart') }} <span style="color:var(--fn-muted); font-weight:600; font-size:15px;">({{ $summary->count }})</span></strong>
      <button type="button" @click="close()" aria-label="{{ __('Close') }}" style="width:40px; height:40px; border-radius:12px; border:1px solid var(--fn-line); background:#FFFFFF; font-size:18px; cursor:pointer; color:var(--fn-ink);">×</button>
    </div>

@if (empty($summary->lines))
      <div style="flex:1 1 auto; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:12px; padding:32px;">
        <span style="display:inline-flex; width:72px; height:72px; border-radius:50%; background:var(--fn-primary-tint); align-items:center; justify-content:center;"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2.2l2.3 10.4A2 2 0 0 0 9.45 16H18a2 2 0 0 0 1.95-1.55L21.5 7.5H6" stroke="var(--fn-primary)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="10" cy="20" r="1.4" fill="var(--fn-primary)"></circle><circle cx="18" cy="20" r="1.4" fill="var(--fn-primary)"></circle></svg></span>
        <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:19px;">{{ __('Your cart is empty') }}</strong>
        <span style="font-size:14px; color:var(--fn-muted);">{{ __('Add something fresh from today\'s picks.') }}</span>
        <button type="button" @click="close()" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 24px; margin-top:6px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:15px; border-radius:999px; cursor:pointer;"><span style="white-space:nowrap;">{{ __('Start shopping') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
      </div>
@else
      @if ($delivery->freeThreshold())
      <div style="padding:14px 22px; background:var(--fn-primary-tint); font-size:13.5px; color:var(--fn-primary-dark);">
        <span>{{ $toFree ? __('Add :amount more for free delivery', ['amount' => \App\Themes\Freshnest\Price::show($toFree)]) : __("You've unlocked free delivery") }}</span>
        <span style="display:block; height:7px; border-radius:999px; background:#FFFFFF; margin-top:9px; overflow:hidden;"><span style="display:block; height:100%; width:{{ $delivery->freePercent($goods) }}%; background:var(--fn-primary); border-radius:999px;"></span></span>
      </div>
      @endif
      <div style="flex:1 1 auto; overflow-y:auto; padding:16px 22px; display:flex; flex-direction:column; gap:12px;">
        @foreach ($summary->notices as $notice)
          <p role="status" style="margin:0; font-size:13px; color:#D4380D;">{{ $notice }}</p>
        @endforeach
        @foreach ($summary->lines as $line)
          @php
              $id = $line->item->id;
              $qty = $line->item->quantity;
              $media = $line->variant->image ?? $line->product->media->first();
              $url = store_route('products.show', ['slug' => $line->product->slug]);
              $atMax = $line->available !== null && $qty >= $line->available;
              $size = $line->variant->title();
          @endphp
          <div style="display:flex; gap:12px; align-items:center; background:#FFFFFF; border:1px solid var(--fn-line); border-radius:16px; padding:10px;">
            <a href="{{ $url }}" aria-label="{{ $line->product->name }}" style="flex:0 0 auto; width:68px; height:68px; border-radius:12px; background:var(--fn-primary-tint){{ $media ? ' url('.$media->url('thumb').')' : '' }} center/cover no-repeat;"></a>
            <div style="flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:4px;">
              <a href="{{ $url }}" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:14.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--fn-ink);">{{ $line->product->name }}</a>
              <span style="font-size:12.5px; color:var(--fn-muted);">{{ $size ? $size.' · ' : '' }}{{ \App\Themes\Freshnest\Price::show($line->unitPrice) }}</span>
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:2px;">
                <span style="display:inline-flex; align-items:center; border:1.5px solid var(--fn-primary-pale); border-radius:10px;">
                  <button type="button" @click="setQuantity({{ $id }}, {{ $qty - 1 }})" :disabled="busy" aria-label="{{ __('Decrease') }}" style="width:30px; height:30px; border:0; background:transparent; color:var(--fn-primary-dark); font-size:16px; cursor:pointer;">−</button>
                  <span style="min-width:22px; text-align:center; font-weight:700; font-size:14px;">{{ $qty }}</span>
                  <button type="button" @click="setQuantity({{ $id }}, {{ $qty + 1 }})" :disabled="busy" @disabled($atMax) aria-label="{{ __('Increase') }}" style="width:30px; height:30px; border:0; background:transparent; color:var(--fn-primary-dark); font-size:16px; cursor:pointer;{{ $atMax ? ' opacity:.35; cursor:default;' : '' }}">+</button>
                </span>
                <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; color:var(--fn-primary);">{{ \App\Themes\Freshnest\Price::show($line->lineTotal) }}</strong>
              </div>
            </div>
            <button type="button" @click="remove({{ $id }})" :disabled="busy" aria-label="{{ __('Remove') }}" style="align-self:flex-start; width:28px; height:28px; border:0; background:transparent; color:var(--fn-muted); font-size:18px; cursor:pointer;">×</button>
          </div>
        @endforeach
      </div>
      <div style="padding:18px 22px 22px; background:#FFFFFF; border-top:1px solid var(--fn-line); display:flex; flex-direction:column; gap:9px;">
        <div style="display:flex; justify-content:space-between; font-size:14px; color:var(--fn-muted);"><span>{{ __('Subtotal') }}</span><span style="color:var(--fn-ink); font-weight:600;">{{ \App\Themes\Freshnest\Price::show($summary->subtotal) }}</span></div>
        @include('storefront.partials.savings-rows', ['summary' => $summary, 'size' => 14])
        <div style="display:flex; justify-content:space-between; font-size:14px; color:var(--fn-muted);"><span>{{ __('Delivery') }}</span><span style="color:var(--fn-ink); font-weight:600;">{{ $fee === null ? __('At checkout') : ($fee->isZero() ? __('Free') : \App\Themes\Freshnest\Price::show($fee)) }}</span></div>
        <div style="display:flex; justify-content:space-between; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:18px; padding-top:8px; border-top:1px dashed var(--fn-primary-pale);"><span>{{ __('Total') }}</span><span>{{ \App\Themes\Freshnest\Price::show($total) }}</span></div>
        <a class="fh-151e18" href="{{ store_route('checkout') }}" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 18px; margin-top:6px; border:0; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:16px; border-radius:999px; cursor:pointer; box-shadow:0 12px 24px rgba(138,79,19,.28);"><span style="white-space:nowrap;">{{ __('Checkout ·') }} {{ \App\Themes\Freshnest\Price::show($total) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
      </div>
@endif
