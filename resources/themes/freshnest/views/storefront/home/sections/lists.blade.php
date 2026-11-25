{{-- Top Rated / Best Sellers / New Arrivals (by rating, number of ratings, newest) and the
     card beside them — Content → Banners → "Card beside the product lists". --}}
@php
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $lists = ['Top Rated' => $home->topRated(), 'Best Sellers' => $home->bestSellers(), 'New Arrivals' => $home->newest()];
    $listIcons = ['Top Rated' => 'star', 'Best Sellers' => 'shopping-cart', 'New Arrivals' => 'sparkles'];
    $seasonal = $home->banner('seasonal');
@endphp
@if ($home->products()->isNotEmpty())
  <section class="wrap" style="padding-top:clamp(40px,5vw,64px);">
    <div class="g2-list3">
      @foreach ($lists as $title => $items)
        <div>
          <h3 class="fn-card-label" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:19px; margin:0 0 16px; padding-bottom:12px; border-bottom:1px solid var(--fn-line); position:relative;"><x-icon :name="$listIcons[$title]" class="fn-card-label-icon" />{{ __($title) }}<span style="position:absolute; left:0; bottom:-1px; width:48px; height:2px; background:var(--fn-accent);"></span></h3>
          <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach ($items as $product)
              @php $p = $home->card($product); @endphp
              <div class="fh-5d63f2" style="background:#FFFFFF; display:flex; align-items:center; gap:14px; padding:10px; border:1px solid var(--fn-line); border-radius:14px;">
                <a href="{{ $p['url'] }}" style="flex:0 0 auto; width:76px; height:76px; border-radius:10px; overflow:hidden; background:var(--fn-surface-muted);">@if ($p['image'])<img src="{{ $p['image']->url('thumb') }}" alt="{{ $p['name'] }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;" />@endif</a>
                <span style="flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:4px;">
                  <a class="fh-ec93c1" href="{{ $p['url'] }}" style="font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#000000;">{{ $p['name'] }}</a>
                  <span style="font-size:12px; color:#6F767D;"><span style="color:#FFB020;">★</span> {{ $p['rating'] }} ({{ $p['reviews'] }})</span>
                  <span style="display:flex; align-items:baseline; gap:6px;"><strong style="font-size:15px; color:var(--fn-ink);">{{ $money($p['price']) }}</strong>@if ($p['old'])<span style="font-size:12px; color:#A3A9AE; text-decoration:line-through;">{{ $money($p['old']) }}</span>@endif</span>
                </span>
                <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:0; flex:0 0 auto;">@csrf<input type="hidden" name="variant_id" value="{{ $p['variant_id'] }}"><input type="hidden" name="quantity" value="1"><button class="fh-18ba34" type="submit" :disabled="busy" aria-label="Add {{ $p['name'] }} to cart" style="flex:0 0 auto; width:34px; height:34px; border-radius:50%; border:0; background:var(--fn-accent-tint); color:var(--fn-accent-dark); cursor:pointer; display:flex; align-items:center; justify-content:center;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></button></form>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
      @if ($seasonal)
      <a href="{{ $seasonal->href() ?: store_route('shop') }}" class="g2-xl fh-17765c" style="position:relative; border-radius:20px; overflow:hidden; min-height:420px; background:#F4F1EA{{ $seasonal->image ? ' url('.$seasonal->image->url('medium').')' : '' }} center/cover no-repeat;">
        <span style="position:absolute; inset:0; background:linear-gradient(180deg, rgba(20,24,28,.65) 0%, rgba(20,24,28,0) 55%);"></span>
        <span style="position:absolute; left:26px; right:26px; top:26px; display:flex; flex-direction:column; gap:8px;">
          <span style="font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#FFD2B0;">{{ $seasonal->eyebrow }}</span>
          <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; line-height:1.15; color:#FFFFFF;">{{ $seasonal->title }}</strong>
          <span style="align-self:flex-start; margin-top:6px; display:inline-flex; align-items:center; height:38px; padding:0 18px; border-radius:999px; background:var(--fn-accent); color:#FFFFFF; font-size:13.5px; font-weight:700;">{{ $seasonal->button_label ?: __('Shop Now') }}</span>
        </span>
      </a>
      @endif
    </div>
  </section>
@endif
