{{--
    Hero — the design's green panel. Content → Banners:
      "Top of home page" (hero)  eyebrow = pill, headline = title (text after "|" is orange),
                                 text, button, link, picture
      "Figure under the hero"    each banner = one figure (headline "80 mi", text "Local growers");
                                 the 2nd also fills the "45 Minutes" card, the 3rd the round badge
    The rating line is the store's own average; the product card is the first featured product.
--}}
@php
    $hero = $home->banner('hero');
    $stats = $home->banners('hero_stat')->take(3)->values();
    $speed = $stats->get(1);
    $badge = $stats->get(2);
    $rating = $home->storeRating();
    $pickProduct = $home->products()->firstWhere('is_featured', true) ?? $home->products()->first();
    $pick = $pickProduct ? $home->card($pickProduct) : null;
    $money = fn ($m) => \App\Themes\Freshnest\Price::show($m);
    [$titleA, $titleB] = array_pad(explode('|', (string) $hero?->title, 2), 2, '');
    $statIcons = ['<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent-light)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"></path><circle cx="12" cy="9.5" r="2.5"></circle></svg>','<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent-light)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M12 7.5V12l3 2"></path></svg>','<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent-light)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z"></path><path d="M3 21 14 10"></path></svg>'];
@endphp
@if ($hero)
  <section id="top" class="wrap" style="padding-top:clamp(14px,2vw,24px);">
    <div class="home-hero">
      <div class="hero-grid">
        <div style="display:flex; flex-direction:column; gap:22px;">
          <span style="align-self:flex-start; display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); color:#FFFFFF; font-size:13px; font-weight:600; padding:8px 14px; border-radius:999px; backdrop-filter:blur(6px);">
            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--fn-primary-light); box-shadow:0 0 0 4px rgba(143,215,155,.25);"></span>
            {{ $hero->eyebrow }}
          </span>
          <h1 class="home-hero-title">{{ $titleA }}@if (filled($titleB))<span>{{ $titleB }}</span>@endif</h1>
          <p class="home-hero-description">{{ $hero->text }}</p>
          <div class="home-hero-actions">
            <a class="home-hero-button home-hero-button-primary" href="{{ $hero->href() ?: store_route('shop') }}">{{ $hero->button_label ?: __('Shop Now') }}</a>
            <a class="home-hero-button" href="{{ store_route('pages.show', ['slug' => 'offers']) }}">{{ __('View Offers') }}</a>
          </div>
          <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <div style="display:flex;">
              @foreach ([0, 1, 2, 0] as $i => $av)
                <span style="display:block; width:38px; height:38px; border-radius:50%; border:3px solid var(--fn-primary-dark); margin-left:{{ $i ? '-12px' : '0' }}; background:var(--fn-primary-light) url({{ asset('themes/freshnest/avatar-'.$av.'.png') }}) center/cover no-repeat;"></span>
              @endforeach
            </div>
            <span style="display:flex; flex-direction:column; gap:2px;">
              <span style="color:var(--fn-accent-light); font-size:15px; letter-spacing:2px;">★★★★★</span>
              <span style="font-size:14px; color:var(--fn-on-dark);"><strong style="font-family:'Plus Jakarta Sans',sans-serif; color:#FFFFFF;">{{ $rating['average'] }}</strong> {{ __('from') }} <strong style="font-family:'Plus Jakarta Sans',sans-serif; color:#FFFFFF;">{{ number_format($rating['count']) }}</strong> {{ __('ratings') }}</span>
            </span>
          </div>
          @if ($stats->isNotEmpty())
          <div style="display:flex; flex-wrap:wrap; gap:14px 0; padding-top:20px; border-top:1px solid rgba(255,255,255,.14); margin-top:4px;">
            @foreach ($stats as $i => $stat)
            @if ($i)
            <span style="width:1px; background:rgba(255,255,255,.16); margin-right:clamp(14px,2vw,26px);"></span>
            @endif
            <div style="display:flex; align-items:center; gap:12px; padding-right:clamp(14px,2vw,26px);"><span style="display:inline-flex; width:40px; height:40px; flex:0 0 auto; border-radius:12px; background:rgba(255,255,255,.12); align-items:center; justify-content:center;">{!! $statIcons[$i % 3] !!}</span><span style="display:flex; flex-direction:column; gap:2px;"><strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(20px,2vw,26px); color:#FFFFFF; letter-spacing:-.02em;">{{ $stat->title }}</strong><span style="font-size:12.5px; color:var(--fn-on-dark-muted);">{{ $stat->text }}</span></span></div>
            @endforeach
          </div>
          @endif
        </div>

        <div class="hero-art" style="position:relative;">
          <div class="home-hero-photo">
            @include('storefront.home.partials.img', ['media' => $hero->image, 'fallback' => asset('themes/freshnest/hero.png'), 'alt' => $hero->title, 'size' => 'large', 'sizes' => '(min-width: 900px) 50vw, 100vw', 'loading' => 'eager'])
          </div>
          @if ($badge)
          <span style="position:absolute; right:-6px; top:18%; display:flex; flex-direction:column; align-items:center; justify-content:center; width:96px; height:96px; border-radius:50%; background:var(--fn-accent); color:#FFFFFF; box-shadow:0 16px 34px rgba(138,79,19,.45); transform:rotate(12deg); text-align:center; line-height:1;">
            <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px;">{{ $badge->title }}</strong>
            <span style="font-size:11px; font-weight:700; letter-spacing:.08em; margin-top:3px;">{{ mb_strtoupper(\Illuminate\Support\Str::afterLast(trim((string) $badge->text), ' ')) }}</span>
          </span>
          @endif
          @if ($speed)
          <div style="position:absolute; left:0; top:clamp(40px,8%,70px); display:flex; align-items:center; gap:12px; background:#FFFFFF; color:var(--fn-ink); padding:12px 16px; border-radius:18px; box-shadow:0 18px 40px rgba(0,0,0,.22);">
            <span style="display:inline-flex; width:42px; height:42px; border-radius:50%; background:var(--fn-primary-tint); align-items:center; justify-content:center;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="var(--fn-primary)" stroke-width="1.8"></circle><path d="M12 7.5V12l3 2" stroke="var(--fn-primary)" stroke-width="1.8" stroke-linecap="round"></path></svg></span>
            <span style="line-height:1.3;"><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:16px;">{{ $speed->title }}</strong><span style="font-size:12.5px; color:var(--fn-muted);">{{ $speed->text }}</span></span>
          </div>
          @endif
          @if ($pick)
          <div style="position:absolute; right:clamp(8px,2vw,24px); bottom:clamp(20px,4%,36px); display:flex; align-items:center; gap:12px; width:min(250px,70%); background:rgba(255,255,255,.96); color:var(--fn-ink); padding:10px; border-radius:20px; box-shadow:0 18px 40px rgba(0,0,0,.22);">
            <a href="{{ $pick['url'] }}" aria-label="{{ $pick['name'] }}" style="flex:0 0 auto; width:56px; height:56px; border-radius:14px; background:var(--fn-primary-tint){{ $pick['image'] ? ' url('.$pick['image']->url('thumb').')' : '' }} center/cover no-repeat;"></a>
            <span style="flex:1 1 auto; min-width:0; line-height:1.3;">
              <a href="{{ $pick['url'] }}" style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:14.5px; color:var(--fn-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $pick['name'] }}</a>
              <span style="display:flex; align-items:baseline; gap:6px;"><strong style="font-size:15px; color:var(--fn-ink);">{{ $money($pick['price']) }}</strong>@if ($pick['old'])<span style="font-size:12px; color:var(--fn-muted); text-decoration:line-through;">{{ $money($pick['old']) }}</span>@endif</span>
            </span>
            <form method="POST" action="{{ store_route('cart.add') }}" x-data="addToCart" @submit.prevent="submit($event)" style="margin:0; flex:0 0 auto;">@csrf<input type="hidden" name="variant_id" value="{{ $pick['variant_id'] }}"><input type="hidden" name="quantity" value="1"><button type="submit" :disabled="busy" aria-label="Add {{ $pick['name'] }} to cart" style="display:inline-flex; width:34px; height:34px; border:0; padding:0; cursor:pointer; border-radius:50%; background:var(--fn-primary); align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></button></form>
          </div>
          @endif
        </div>
      </div>
    </div>
  </section>
@endif
