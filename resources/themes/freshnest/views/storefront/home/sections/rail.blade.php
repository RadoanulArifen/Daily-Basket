{{-- Daily rail: products from the 4th and 3rd categories (Bakery, Dairy in the design),
     the heading names them. --}}
@php
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $railCats = $home->tabs()->slice(2, 2)->reverse()->values();
    $rail = $railCats->flatMap(fn ($t) => $home->inCategory($t['slug'], 4))->take(6)->values();
    $railTitle = __('Daily :aisles', ['aisles' => $railCats->pluck('name')->implode(' & ')]);
    $railLink = $railCats->last() ? store_route('collections.show', ['slug' => $railCats->last()['slug']]) : store_route('shop');
@endphp
@if ($rail->isNotEmpty())
  <section class="wrap" style="padding-top:clamp(40px,5vw,64px);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:20px;">
      <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(22px,2.4vw,30px); letter-spacing:-.02em; margin:0;">{{ $railTitle }}</h2>
      <a class="fh-ed87d8" href="{{ $railLink }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-ink); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px; white-space:nowrap;">
        {{ __('Show All') }}
        <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
      </a>
    </div>
    <div class="g2-pgrid">
      @foreach ($rail as $product)
        @php $p = $home->card($product); @endphp
        <div class="fh-f16072" style="position:relative; display:flex; flex-direction:column; gap:10px; padding:12px; border:1px solid var(--fn-line); border-radius:16px; background:#FFFFFF;">
          <a href="{{ $p['url'] }}" style="display:block; aspect-ratio:1/1; border-radius:12px; overflow:hidden; background:var(--fn-surface-muted);">@if ($p['image'])<img src="{{ $p['image']->url('medium') }}" alt="{{ $p['name'] }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;" />@endif</a>
          @if ($p['pct'])<span style="position:absolute; left:20px; top:20px; padding:4px 8px; border-radius:8px; background:var(--fn-accent); color:#FFFFFF; font-size:11.5px; font-weight:700;">-{{ $p['pct'] }}%</span>@endif
          <form method="POST" action="{{ store_route('wishlist.toggle', ['product' => $product->id]) }}" x-data="wishlistButton({{ $p['wished'] ? 'true' : 'false' }})" @submit.prevent="toggle($event)" style="margin:0;">@csrf<button type="submit" :aria-pressed="saved" aria-label="Add {{ $p['name'] }} to wishlist" style="position:absolute; right:20px; top:20px; width:32px; height:32px; border-radius:50%; border:0; background:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,.08);"><svg width="15" height="15" viewBox="0 0 24 24" fill="{{ $p['wished'] ? 'var(--fn-accent)' : 'none' }}" :fill="saved ? 'var(--fn-accent)' : 'none'" stroke="{{ $p['wished'] ? 'var(--fn-accent)' : '#6F767D' }}" :stroke="saved ? 'var(--fn-accent)' : '#6F767D'" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20Z"></path></svg></button></form>
          <span style="font-size:12px; color:#6F767D;">{{ $p['category'] }}{{ $p['weight'] ? ' · '.$p['weight'] : '' }}</span>
          <a href="{{ $p['url'] }}" class="g2-clamp2 fh-ec93c1" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:14.5px; line-height:1.3; min-height:38px; color:#000000;">{{ $p['name'] }}</a>
          <div style="margin-top:auto; display:flex; align-items:center; justify-content:space-between; gap:8px;">
            <span style="display:flex; align-items:baseline; gap:6px; flex-wrap:wrap;"><strong style="font-size:17px; color:var(--fn-primary);">{{ $money($p['price']) }}</strong>@if ($p['old'])<span style="font-size:12.5px; color:#A3A9AE; text-decoration:line-through;">{{ $money($p['old']) }}</span>@endif</span>
            <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:0; flex:0 0 auto;">@csrf<input type="hidden" name="variant_id" value="{{ $p['variant_id'] }}"><input type="hidden" name="quantity" value="1"><button class="fh-18ba34" type="submit" :disabled="busy" aria-label="Add {{ $p['name'] }} to cart" style="flex:0 0 auto; height:36px; padding:0 12px; border-radius:999px; border:0; background:var(--fn-accent-tint); color:var(--fn-accent-dark); font-weight:700; font-size:13px; display:flex; align-items:center; gap:5px; cursor:pointer;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>{{ __('Add') }}</button></form>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endif
