{{--
    Weekend offer + side offer. Content → Banners:
      "Big offer with countdown" (weekend)  eyebrow, headline, text = "30% OFF|the sentence",
                                            button, link, picture; the countdown runs to "Ends"
      "Offer beside the countdown"          eyebrow, headline, text, button, link, picture; the
                                            round badge shows the deal (Marketing → Deals) on the
                                            category the link points to, if there is one
--}}
@php
    $weekend = $home->banner('weekend');
    $side = $home->banner('side_promo');
    [$figure, $lead] = array_pad(explode('|', (string) $weekend?->text, 2), 2, '');
    if ($lead === '') { [$figure, $lead] = ['', $figure]; }
    preg_match('/^\s*([\d.,]+)\s*(%|\$)?\s*(.*)$/u', $figure, $fig);
    [$figNum, $figSign, $figWord] = [$fig[1] ?? trim($figure), $fig[2] ?? '', trim($fig[3] ?? '')];
    $lineCount = $home->products()->count();
    $aisles = $home->categories()->where('count', '>', 0)->count().' aisles, all certified';
    $sideSlug = preg_match('#/collections/([a-z0-9-]+)#', (string) $side?->url, $m) ? $m[1] : null;
    $sideCategory = $sideSlug ? \App\Models\Category::query()->where('slug', $sideSlug)->first() : null;
    $sideDeal = $sideCategory ? $home->promotions()->first(fn ($p) => $p->applies_to === 'category' && (int) $p->category_id === $sideCategory->id) : null;
@endphp
@if ($weekend || $side)
  <section id="offers" class="wrap promo-grid" style="padding-top:clamp(32px,4.5vw,56px);">
    @if ($weekend)
    <article class="promo-main fn-icon-card" style="position:relative; overflow:hidden; border-radius:28px; background:radial-gradient(130% 140% at 0% 0%, var(--fn-primary) 0%, var(--fn-primary-dark) 55%, var(--fn-primary-deep) 100%); color:#FFFFFF; box-shadow:0 26px 54px rgba(25,75,48,.22);">
      <x-icon name="badge-percent" class="fn-card-watermark" />
      <svg width="200" height="200" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; left:40%; top:-60px; opacity:.07; transform:rotate(30deg);"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="#FFFFFF"></path></svg>
      <div style="position:relative; padding:clamp(26px,3.4vw,44px); display:flex; flex-direction:column; gap:16px; justify-content:center;">
        <span style="align-self:flex-start; display:inline-flex; align-items:center; gap:8px; background:var(--fn-accent-light); color:var(--fn-ink); font-size:12px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; padding:7px 13px; border-radius:999px;">
          <x-icon name="tag" class="fn-card-label-icon" />
          {{ $weekend->eyebrow }}
        </span>
        <h3 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(22px,2.4vw,30px); letter-spacing:-.02em; margin:0; line-height:1.15; color:var(--fn-primary-pale);">{{ $weekend->title }}</h3>
        @if (filled($figure))
        <div style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
          <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(64px,8.5vw,112px); line-height:.82; letter-spacing:-.05em; color:#FFFFFF;">{{ $figNum }}@if ($figSign)<span style="color:var(--fn-accent);">{{ $figSign }}</span>@endif</span>
          @if (filled($figWord))<span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(26px,3vw,40px); line-height:1; color:var(--fn-accent-light); padding-bottom:4px;">{{ $figWord }}</span>@endif
        </div>
        @endif
        <p style="margin:0; font-size:16px; line-height:1.55; color:var(--fn-on-dark); max-width:380px;">{{ $lead }}</p>
        @if ($weekend->ends_at)
        <div x-data="fnCountdown(@js($weekend->ends_at->toIso8601String()), true, @js([__('Days'), __('Hours'), __('Mins'), __('Secs')]))">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:4px;">
          <template x-for="unit in units" :key="unit.label">
            <span style="display:flex; flex-direction:column; align-items:center; min-width:58px; background:#FFFFFF; color:var(--fn-ink); border-radius:14px; padding:9px 6px 8px; box-shadow:0 8px 18px rgba(0,0,0,.15);">
              <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:22px; line-height:1;" x-text="unit.value"></strong>
              <span style="font-size:10px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--fn-muted); margin-top:4px;" x-text="unit.label"></span>
            </span>
          </template>
        </div>
        </div>
        @endif
        <a class="fh-151e18" href="{{ $weekend->href() ?: store_route('pages.show', ['slug' => 'offers']) }}" style="align-self:flex-start; display:inline-flex; align-items:center; gap:12px; margin-top:8px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:15.5px; padding:6px 6px 6px 22px; border-radius:999px; box-shadow:0 12px 28px rgba(138,79,19,.35);"><span style="white-space:nowrap;">{{ $weekend->button_label ?: __('Grab the Deal') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
      </div>
      <div class="promo-art" style="position:relative;">
        <span style="position:absolute; inset:clamp(14px,1.6vw,20px); border-radius:22px; overflow:hidden; box-shadow:0 18px 40px rgba(0,0,0,.25);">
          @include('storefront.home.partials.img', ['media' => $weekend->image, 'alt' => $weekend->title, 'sizes' => '(min-width: 900px) 30vw, 100vw'])
        </span>
        <span style="position:absolute; right:clamp(26px,3vw,36px); bottom:clamp(26px,3vw,36px); display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.95); color:var(--fn-ink); border-radius:14px; padding:10px 14px; box-shadow:0 10px 24px rgba(0,0,0,.18);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="var(--fn-primary)"></path><path d="M3 21C8 16 13 11 21 3" stroke="#FFFFFF" stroke-width="1.6" stroke-linecap="round"></path></svg>
          <span style="line-height:1.25;"><strong style="display:block; font-size:13.5px;">{{ $lineCount }} {{ __('organic') }} {{ $lineCount === 1 ? __('line') : __('lines') }}</strong><span style="font-size:12px; color:var(--fn-muted);">{{ $aisles }}</span></span>
        </span>
      </div>
    </article>
@endif

@if ($side)
    <article style="position:relative; overflow:hidden; border-radius:28px; min-height:320px; display:flex; flex-direction:column; justify-content:flex-end; color:#FFFFFF; box-shadow:0 22px 44px rgba(25,75,48,.16);">
      <span style="position:absolute; inset:0;">@include('storefront.home.partials.img', ['media' => $side->image, 'alt' => $side->title, 'sizes' => '(min-width: 900px) 34vw, 100vw'])</span>
      <span style="position:absolute; inset:0; background:linear-gradient(180deg, rgba(20,40,20,.05) 20%, rgba(20,40,20,.82) 100%); pointer-events:none;"></span>
      @if ($sideDeal)
      <span style="position:absolute; right:20px; top:20px; display:flex; flex-direction:column; align-items:center; justify-content:center; width:92px; height:92px; border-radius:50%; background:var(--fn-accent-light); color:var(--fn-ink); box-shadow:0 14px 30px rgba(138,79,19,.4); transform:rotate(10deg); text-align:center; line-height:1;">
        <span style="font-size:11px; font-weight:700; letter-spacing:.06em;">{{ __('BUY') }} {{ $sideDeal->buy_quantity }}</span>
        <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:20px; margin:3px 0;">{{ __('GET') }} {{ $sideDeal->get_quantity }}</strong>
        <span style="font-size:11px; font-weight:700; letter-spacing:.06em;">{{ $sideDeal->get_percent >= 100 ? 'FREE' : $sideDeal->get_percent.'% OFF' }}</span>
      </span>
      @endif
      <div style="position:relative; padding:clamp(22px,2.6vw,30px); display:flex; flex-direction:column; gap:10px;">
        <span style="align-self:flex-start; background:rgba(255,255,255,.18); backdrop-filter:blur(6px); font-size:12px; font-weight:700; padding:6px 12px; border-radius:999px;">{{ $side->eyebrow }}</span>
        <h3 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(26px,2.6vw,34px); letter-spacing:-.02em; margin:0; line-height:1.05;">{{ $side->title }}</h3>
        <p style="margin:0; font-size:14.5px; line-height:1.55; color:var(--fn-on-dark); max-width:320px;">{{ $side->text }}</p>
        <a class="fh-74ffb8" href="{{ $side->href() ?: store_route('shop') }}" style="align-self:flex-start; display:inline-flex; align-items:center; gap:12px; margin-top:6px; background:#FFFFFF; color:var(--fn-primary-dark); font-weight:700; font-size:14.5px; padding:6px 6px 6px 20px; border-radius:999px;"><span style="white-space:nowrap;">{{ $side->button_label ?: __('Shop now') }}</span><span style="display:inline-flex; width:32px; height:32px; flex:0 0 auto; border-radius:50%; background:var(--fn-primary-dark); align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
      </div>
    </article>
@endif
  </section>
@endif
