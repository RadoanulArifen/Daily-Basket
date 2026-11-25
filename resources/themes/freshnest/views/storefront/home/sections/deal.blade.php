{{--
    Deal of the Day. Content → Banners → "Deal of the Day": eyebrow, headline (text after "|"
    is orange), text, and the link = the product (e.g. /products/almond-croissants); without
    a link the biggest saving is shown. The clock runs to the banner's "Ends", else midnight.
    Daily essentials: the biggest saving in each category. "Notify me" goes to the newsletter.
--}}
@php
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $banner = $home->banner('deal');
    $dealProduct = $home->dealProduct();
    $d = $dealProduct ? $home->card($dealProduct) : null;
    $essentials = $home->essentials(4, $dealProduct);
    [$titleA, $titleB] = array_pad(explode('|', (string) ($banner?->title ?: __('Deal of the |Day')), 2), 2, '');
    $unitText = $d ? str_replace('9.99', '__', $money(\App\Support\Money::of('9.99', $d['price']->currency))) : '';
@endphp
@if ($d)
  <section id="dairy" style="margin-top:clamp(40px,5vw,68px); position:relative; overflow:hidden; background:var(--fn-accent-tint); padding-top:clamp(48px,6vw,88px); padding-bottom:clamp(48px,6vw,88px);">
    <span style="position:absolute; right:-120px; top:-120px; width:420px; height:420px; border-radius:50%; background:#FFE9D6;"></span>
    <svg width="220" height="220" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; left:-50px; bottom:-40px; opacity:.6; transform:rotate(-150deg);"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="var(--fn-primary-tint)"></path></svg>
    <div class="wrap" style="position:relative;">
      <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:20px;">
        <div style="max-width:640px;">
          <span style="display:inline-flex; align-items:center; gap:8px; background:#FFFFFF; border:1px solid #FFD9BC; color:var(--fn-accent-dark); font-size:12.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:8px 14px; border-radius:999px;">
            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--fn-accent);"></span>
            {{ $banner?->eyebrow ?: __('Resets at midnight') }}
          </span>
          <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(30px,4.2vw,50px); line-height:1.05; letter-spacing:-.03em; margin:16px 0 0; color:var(--fn-ink);">{{ $titleA }}@if (filled($titleB))<span style="color:var(--fn-accent);">{{ $titleB }}</span>@endif</h2>
          <p style="margin:12px 0 0; font-size:16px; line-height:1.6; color:var(--fn-muted);">{{ $banner?->text }}</p>
        </div>
        <div x-data="fnCountdown(@js($banner?->ends_at?->toIso8601String()), false, @js([__('Hrs'), __('Min'), __('Sec')]))" style="display:flex; align-items:center; gap:10px;">
          <span style="font-size:13px; font-weight:600; color:var(--fn-muted);">{{ __('Ends in') }}</span>
          <template x-for="u in units" :key="u.label">
            <span style="display:flex; flex-direction:column; align-items:center; min-width:62px; background:var(--fn-ink); color:#FFFFFF; border-radius:14px; padding:10px 8px;">
              <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; line-height:1;" x-text="u.value"></strong>
              <span style="font-size:10.5px; letter-spacing:.08em; text-transform:uppercase; color:#B8C4B8; margin-top:5px;" x-text="u.label"></span>
            </span>
          </template>
        </div>
      </div>

      <div class="deal-grid" style="margin-top:clamp(28px,3.5vw,44px);">
        <article class="deal-hero fn-icon-card" x-data="{ qty: 1 }" style="position:relative; overflow:hidden; border-radius:28px; background:radial-gradient(120% 120% at 0% 0%, var(--fn-primary) 0%, var(--fn-primary-dark) 60%, var(--fn-primary-deep) 100%); color:#FFFFFF; box-shadow:0 30px 60px rgba(25,75,48,.25);">
          <x-icon name="clock" class="fn-card-watermark" />
          <div style="position:relative; min-height:clamp(260px,30vw,420px);">
            <div style="position:absolute; inset:clamp(14px,1.6vw,20px); border-radius:22px; overflow:hidden;">
              @include('storefront.home.partials.img', ['media' => $d['image'], 'alt' => $d['name'], 'size' => 'large', 'sizes' => '(min-width: 1000px) 50vw, 100vw'])
            </div>
            @if ($d['pct'])
            <span style="position:absolute; left:clamp(28px,3vw,40px); top:clamp(28px,3vw,40px); display:flex; flex-direction:column; align-items:center; justify-content:center; width:88px; height:88px; border-radius:50%; background:var(--fn-accent); color:#FFFFFF; box-shadow:0 14px 30px rgba(138,79,19,.4); transform:rotate(-10deg);">
              <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; line-height:1;">{{ $d['pct'] }}%</strong>
              <span style="font-size:11px; font-weight:700; letter-spacing:.1em;">{{ __('OFF') }}</span>
            </span>
            @endif
          </div>
          <div style="padding:clamp(22px,3vw,36px); padding-top:clamp(6px,1vw,12px); display:flex; flex-direction:column; gap:14px;">
            <span style="font-size:12.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--fn-primary-light);">{{ __('Today\'s hero') }}{{ $d['weight'] ? ' · '.$d['weight'] : '' }}</span>
            <h3 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(24px,2.6vw,32px); letter-spacing:-.02em; margin:0; line-height:1.15;"><a href="{{ $d['url'] }}" style="color:#FFFFFF;">{{ $d['name'] }}</a></h3>
            <div style="display:flex; align-items:baseline; gap:12px; flex-wrap:wrap;">
              <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:40px; letter-spacing:-.03em; color:#FFFFFF;">{{ $money($d['price']) }}</span>
              @if ($d['old'])<span style="font-size:18px; color:var(--fn-on-dark-muted); text-decoration:line-through;">{{ $money($d['old']) }}</span>@endif
            </div>
            @if ($d['stock'] !== null && $d['stock'] > 0 && $d['stock'] <= 50)
            <div style="display:flex; justify-content:flex-end; font-size:13px; color:var(--fn-primary-pale);"><span>{{ __('Only') }} <strong style="color:var(--fn-accent-light);">{{ $d['stock'] }}</strong> {{ __('left') }}</span></div>
            @endif
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:6px;">
              <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="flex:1 1 320px; display:flex; align-items:center; gap:8px; background:#FFFFFF; border-radius:999px; padding:6px; box-shadow:0 14px 30px rgba(0,0,0,.2); min-width:0;">
                <div style="display:flex; align-items:center; gap:4px; background:#F1F7F2; border-radius:999px; padding:4px;">
                  @csrf<input type="hidden" name="variant_id" value="{{ $d['variant_id'] }}"><input type="hidden" name="quantity" :value="qty" value="1">
                  <button class="fh-bb6aec" type="button" @click="qty = Math.max(1, qty - 1)" aria-label="{{ __('Decrease') }}" style="width:36px; height:36px; border-radius:50%; border:0; background:#FFFFFF; color:var(--fn-primary-dark); font-size:18px; font-weight:700; cursor:pointer; box-shadow:0 2px 6px rgba(25,75,48,.12);">−</button>
                  <span style="min-width:26px; text-align:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:16px; color:var(--fn-ink);" x-text="qty">1</span>
                  <button class="fh-92c14d" type="button" @click="qty = Math.min(20, qty + 1)" aria-label="{{ __('Increase') }}" style="width:36px; height:36px; border-radius:50%; border:0; background:var(--fn-primary); color:#FFFFFF; font-size:18px; font-weight:700; cursor:pointer;">+</button>
                </div>
                <button class="fh-92c14d" type="submit" :disabled="busy" style="flex:1 1 auto; min-width:0; display:flex; align-items:center; justify-content:space-between; gap:10px; height:44px; border:0; background:var(--fn-primary); color:#FFFFFF; border-radius:999px; padding:0 5px 0 18px; cursor:pointer;">
                  <span style="display:inline-flex; align-items:center; gap:9px; font-weight:700; font-size:15px; white-space:nowrap;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2.2l2.3 10.4A2 2 0 0 0 9.45 16H18a2 2 0 0 0 1.95-1.55L21.5 7.5H6" stroke="#FFFFFF" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="10" cy="20" r="1.4" fill="#FFFFFF"></circle><circle cx="18" cy="20" r="1.4" fill="#FFFFFF"></circle></svg>
                    {{ __('Add to Cart') }}
                  </span>
                  <span style="display:inline-flex; align-items:center; height:34px; padding:0 14px; border-radius:999px; background:#FFFFFF; color:var(--fn-accent-dark); font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:14.5px; white-space:nowrap;" x-text="@js($unitText).replace('__', (qty * {{ $d['price']->minor }} / 100).toFixed(2))">{{ $money($d['price']) }}</span>
                </button>
              </form>
              <a class="fh-0d46e6" href="{{ $d['url'] }}" aria-label="{{ __('View details') }}" title="{{ __('View details') }}" style="flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; height:56px; padding:0 20px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.3); color:#FFFFFF; font-weight:600; font-size:14.5px; white-space:nowrap;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"></path><circle cx="12" cy="12" r="2.6"></circle></svg>
                {{ __('Details') }}
              </a>
            </div>
          </div>
        </article>

        <div style="display:flex; flex-direction:column; gap:16px; min-width:0;">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:19px; color:var(--fn-ink);">{{ __('Daily essentials') }}</strong>
            <a class="fh-ed87d8" href="{{ store_route('pages.show', ['slug' => 'offers']) }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-ink); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px; white-space:nowrap;">
        {{ __('Show All') }}
        <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
      </a>
          </div>
          <div class="ess-grid">
            @foreach ($essentials as $product)
            @php $x = $home->card($product); @endphp
              <article class="fh-aaea60" style="display:flex; gap:14px; align-items:center; background:#FFFFFF; border:1px solid #F3E2D3; border-radius:20px; padding:12px; box-shadow:0 8px 22px rgba(212,88,10,.06); transition:transform .2s ease, box-shadow .2s ease;">
                <a href="{{ $x['url'] }}" aria-label="{{ $x['name'] }}" style="position:relative; display:block; flex:0 0 auto; width:96px; height:96px; border-radius:16px; overflow:hidden; background:var(--fn-primary-tint);">
                  <span style="position:absolute; inset:0; pointer-events:none;">@include('storefront.home.partials.img', ['media' => $x['image'], 'alt' => '', 'sizes' => '96px'])</span>
                  @if ($x['pct'])<span style="position:absolute; left:6px; top:6px; background:var(--fn-accent-light); color:var(--fn-ink); font-size:10.5px; font-weight:700; padding:4px 7px; border-radius:999px;">{{ $x['pct'] }}%</span>@endif
                </a>
                <div style="flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:5px;">
                  @if ($x['category'])<span class="fn-card-label" style="font-size:11.5px; font-weight:700; color:var(--fn-primary); text-transform:uppercase; letter-spacing:.06em;"><x-icon name="tag" class="fn-card-label-icon" />{{ $x['category'] }}</span>@endif
                  <a href="{{ $x['url'] }}" style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15px; color:var(--fn-ink); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $x['name'] }}</a>
                  <span style="font-size:12.5px; color:var(--fn-muted);">{{ $x['weight'] }}</span>
                  <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:2px;">
                    <span style="display:flex; align-items:baseline; gap:6px;"><strong style="font-family:'Plus Jakarta Sans',sans-serif; font-size:17px; color:var(--fn-primary);">{{ $money($x['price']) }}</strong>@if ($x['old'])<span style="font-size:12.5px; color:var(--fn-muted); text-decoration:line-through;">{{ $money($x['old']) }}</span>@endif</span>
                    <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:0; flex:0 0 auto;">@csrf<input type="hidden" name="variant_id" value="{{ $x['variant_id'] }}"><input type="hidden" name="quantity" value="1"><button class="fh-6763d4" type="submit" :disabled="busy" aria-label="Add {{ $x['name'] }} to cart" style="flex:0 0 auto; width:38px; height:38px; border-radius:12px; border:0; background:var(--fn-primary-tint); color:var(--fn-primary-dark); font-size:20px; font-weight:600; cursor:pointer;">+</button></form>
                  </div>
                </div>
              </article>
            @endforeach
          </div>
          <div style="display:flex; align-items:center; gap:14px; background:var(--fn-ink); color:#FFFFFF; border-radius:20px; padding:18px 20px; margin-top:auto;">
            <span style="display:inline-flex; width:46px; height:46px; flex:0 0 auto; border-radius:14px; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"></path></svg></span>
            <span style="flex:1 1 auto; line-height:1.4;"><strong style="display:block; font-size:15px;">{{ __('Never miss a drop') }}</strong><span style="font-size:13px; color:#B8C4B8;">{{ __('Get tomorrow\'s deal in your inbox at 7am.') }}</span></span>
            <a href="#bakery" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 16px; flex:0 0 auto; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:13.5px; border-radius:999px;"><span style="white-space:nowrap;">{{ __('Notify me') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
          </div>
        </div>
      </div>
    </div>
  </section>
@endif
