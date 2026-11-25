{{--
    Offers & Bundles — Marketing → Deals and Discounts:
      left   a deal on one product that takes two (e.g. "Buy 1, get 1 free")
      middle a fixed-amount discount code with a minimum spend (e.g. FRESH5: $5 off $40+),
             with the visitor's cart total against the minimum (the design's cashback card;
             the store has no wallet, so it is a code)
      right  a deal on one product that takes three or more (e.g. "Buy 3, save 15%")
--}}
@php
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    $bogo = $home->featuredDeal(false);
    $bundle = $home->featuredDeal(true);
    $bundleSave = $bundle ? (int) round((1 - $bundle['total']->minor / max(1, $bundle['card']['price']->multiply($bundle['qty'])->minor)) * 100) : 0;
    $spend = $home->spendDiscount();
    $base = config('store.base_currency');
    $spendMin = $spend?->min_subtotal;
    $spendOff = $spend ? \App\Support\Money::of((string) $spend->value, $base) : null;
    $cartTotal = $spend ? app(\App\Services\Cart\CartService::class)->summary()->subtotal : null;
@endphp
@if ($bogo || $bundle || $spend)
  <section id="offers-bundles" class="wrap" style="padding-top:clamp(40px,5vw,68px);">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
      <div>
        <span style="display:inline-flex; align-items:center; gap:8px; color:var(--fn-accent-dark); font-size:12.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;"><span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--fn-accent);"></span>{{ __('Stack your savings') }}</span>
        <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(25px,3.2vw,36px); letter-spacing:-.02em; margin:8px 0 0; color:var(--fn-ink);">{{ __('Offers & Bundles') }}</h2>
      </div>
      <a class="fh-151e18" href="{{ store_route('pages.show', ['slug' => 'offers']) }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px; white-space:nowrap; box-shadow:0 10px 22px rgba(138,79,19,.28);"><span style="white-space:nowrap;">{{ __('See every offer') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
    </div>
    <div class="hoffer-row" style="margin-top:22px;">
@if ($bogo)
      <article class="fn-icon-card" style="position:relative; overflow:hidden; border-radius:24px; background:linear-gradient(135deg,var(--fn-accent) 0%, #FF9A4A 100%); color:#FFFFFF; display:flex; box-shadow:0 20px 40px rgba(138,79,19,.25);">
        <x-icon name="tag" class="fn-card-watermark" />
        <div style="position:relative; flex:1 1 auto; padding:24px; display:flex; flex-direction:column; gap:10px; min-width:0;">
          <span style="align-self:flex-start; background:#FFFFFF; color:var(--fn-accent-dark); font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:5px 10px; border-radius:999px;">{{ $bogo['promotion']->label() }}</span>
          <div style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:.95; letter-spacing:-.03em; font-size:clamp(26px,2.4vw,32px);"><span style="display:block;">{{ __('BUY') }} {{ $bogo['promotion']->buy_quantity }}</span><span style="display:block; color:#FFFFFF;">{{ __('GET') }} {{ $bogo['promotion']->get_quantity }} {{ $bogo['promotion']->get_percent >= 100 ? 'FREE' : $bogo['promotion']->get_percent.'% OFF' }}</span></div>
          <a href="{{ $bogo['card']['url'] }}" style="font-size:14px; font-weight:700; color:#FFFFFF;">{{ $bogo['card']['name'] }}{{ $bogo['card']['weight'] ? ' · '.$bogo['card']['weight'] : '' }}</a>
          <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:auto 0 0; align-self:flex-start;">@csrf<input type="hidden" name="variant_id" value="{{ $bogo['card']['variant_id'] }}"><input type="hidden" name="quantity" value="{{ $bogo['qty'] }}">
          <button class="fh-41c4b2" type="submit" :disabled="busy" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 16px; margin-top:auto; align-self:flex-start; border:0; background:var(--fn-primary-dark); color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px; cursor:pointer; white-space:nowrap;"><span style="white-space:nowrap;">{{ __('Add') }} {{ $bogo['qty'] }} {{ __('for') }} {{ $money($bogo['total']) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary-dark)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
          </form>
        </div>
        <a href="{{ $bogo['card']['url'] }}" aria-label="{{ $bogo['card']['name'] }}" style="position:relative; flex:0 0 42%; min-height:220px; display:block;">
          <span style="position:absolute; inset:14px 14px 14px 0; border-radius:18px; overflow:hidden; box-shadow:0 12px 24px rgba(0,0,0,.18);">@include('storefront.home.partials.img', ['media' => $bogo['card']['image'], 'alt' => $bogo['card']['name'], 'sizes' => '220px'])</span>
        </a>
      </article>
      @endif

@if ($spend)
      <article class="fn-icon-card" style="position:relative; overflow:hidden; border-radius:24px; background:radial-gradient(120% 140% at 100% 0%, var(--fn-primary) 0%, var(--fn-primary-dark) 60%, var(--fn-primary-deep) 100%); color:#FFFFFF; padding:24px; display:flex; flex-direction:column; gap:12px; box-shadow:0 20px 40px rgba(25,75,48,.22);">
        <svg width="150" height="150" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; right:-30px; bottom:-30px; opacity:.1;"><circle cx="12" cy="12" r="10" stroke="#FFFFFF" stroke-width="1.5"></circle><path d="M12 6v12M15 8.5c-.6-.9-1.7-1.5-3-1.5-1.9 0-3 1-3 2.3 0 3.2 6 1.8 6 5 0 1.4-1.3 2.4-3 2.4-1.4 0-2.6-.6-3.2-1.6" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"></path></svg>
        <span style="position:relative; align-self:flex-start; background:rgba(255,255,255,.16); font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:5px 10px; border-radius:999px;">{{ __('Code') }} {{ $spend->code }}</span>
        <div style="position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:1.05;">
          <span style="display:block; font-size:15px; font-weight:600; color:var(--fn-primary-pale);">{{ __('Spend') }} {{ price_whole($spendMin) }}{{ __(', get') }}</span>
          <span style="display:block; font-size:clamp(38px,3.6vw,50px); color:#FFFFFF; letter-spacing:-.03em;">{{ price_whole($spendOff) }} {{ __('off') }}</span>
        </div>
        <div style="position:relative;">
          <div style="display:flex; justify-content:space-between; font-size:12.5px; color:var(--fn-primary-pale); margin-bottom:7px;"><span>{{ __('Your cart:') }} <strong style="color:#FFFFFF;">{{ $money($cartTotal) }}</strong></span><span>{{ $spendMin->isGreaterThan($cartTotal) ? $money($spendMin->subtract($cartTotal)).' to go' : 'Unlocked ✓' }}</span></div>
          <div style="height:8px; border-radius:999px; background:rgba(255,255,255,.15); overflow:hidden;"><div style="height:100%; width:{{ $spendMin->isZero() ? 100 : min(100, (int) round($cartTotal->minor / $spendMin->minor * 100)) }}%; background:linear-gradient(90deg,var(--fn-primary-light),var(--fn-accent-light)); border-radius:999px;"></div></div>
        </div>
        <span style="position:relative; margin-top:auto; font-size:13px; color:var(--fn-primary-pale);">{{ __('Applies to every product. Enter') }} {{ $spend->code }} {{ __('at checkout.') }}</span>
      </article>
      @endif

@if ($bundle)
      <article class="fn-icon-card" style="position:relative; overflow:hidden; border-radius:24px; background:var(--fn-primary-tint); border:1.5px solid var(--fn-primary-pale); display:flex;">
        <x-icon name="package-check" class="fn-card-watermark" />
        <div style="position:relative; flex:1 1 auto; padding:24px; display:flex; flex-direction:column; gap:10px; min-width:0;">
          <span style="align-self:flex-start; background:var(--fn-primary-dark); color:#FFFFFF; font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:5px 10px; border-radius:999px;">{{ $bundle['promotion']->label() }}</span>
          <div style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; line-height:.95; letter-spacing:-.03em; font-size:clamp(26px,2.4vw,32px);"><span style="display:block; color:var(--fn-primary-dark);">{{ __('BUY') }} {{ $bundle['qty'] }}</span><span style="display:block; color:var(--fn-accent);">{{ __('SAVE') }} {{ $bundleSave }}%</span></div>
          <a href="{{ $bundle['card']['url'] }}" style="font-size:14px; font-weight:700; color:var(--fn-ink);">{{ $bundle['card']['name'] }}{{ $bundle['card']['weight'] ? ' · '.$bundle['card']['weight'] : '' }}</a>
          <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:auto 0 0; align-self:flex-start;">@csrf<input type="hidden" name="variant_id" value="{{ $bundle['card']['variant_id'] }}"><input type="hidden" name="quantity" value="{{ $bundle['qty'] }}">
          <button class="fh-f80239" type="submit" :disabled="busy" style="display:inline-flex; align-items:center; justify-content:center; gap:12px; padding:6px 6px 6px 16px; margin-top:auto; align-self:flex-start; border:0; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:14px; border-radius:999px; cursor:pointer; white-space:nowrap;"><span style="white-space:nowrap;">{{ __('Add') }} {{ $bundle['qty'] }} {{ __('for') }} {{ $money($bundle['total']) }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></button>
          </form>
        </div>
        <a href="{{ $bundle['card']['url'] }}" aria-label="{{ $bundle['card']['name'] }}" style="position:relative; flex:0 0 42%; min-height:220px; display:block;">
          <span style="position:absolute; inset:14px 14px 14px 0; border-radius:18px; overflow:hidden; box-shadow:0 12px 24px rgba(25,75,48,.14);">@include('storefront.home.partials.img', ['media' => $bundle['card']['image'], 'alt' => $bundle['card']['name'], 'sizes' => '220px'])</span>
        </a>
      </article>
      @endif
    </div>
  </section>
@endif
