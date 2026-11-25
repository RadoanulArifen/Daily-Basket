{{--
    Today's Fresh Picks. Tabs: the first four categories with products ("All" shows twelve).
    The three small cards: Content → Banners → "Small cards above Today's Fresh Picks"
    (eyebrow, headline, text, button, link). Deals on a product show as its orange tag.
--}}
@php
    $minis = $home->banners('picks_promo')->take(3)->values();
    $miniStyles = [
        ['bg' => 'var(--fn-primary-tint)', 'kicker' => 'var(--fn-primary)', 'title' => 'var(--fn-primary-dark)', 'body' => 'var(--fn-muted)', 'cta' => 'var(--fn-primary-dark)'],
        ['bg' => 'var(--fn-accent-tint)', 'kicker' => 'var(--fn-accent)', 'title' => 'var(--fn-ink)', 'body' => 'var(--fn-muted)', 'cta' => 'var(--fn-accent)'],
        ['bg' => 'var(--fn-primary-dark)', 'kicker' => 'var(--fn-primary-light)', 'title' => '#FFFFFF', 'body' => 'var(--fn-primary-pale)', 'cta' => '#FFFFFF'],
    ];
    $allCount = 12;
    $cats = $home->tabs();
    $picks = $home->products()->take($allCount)->concat($cats->flatMap(fn ($t) => $home->inCategory($t['slug'], $allCount)))->unique('id')->values();
    $tabs = collect([['name' => __('All'), 'slug' => 'all', 'count' => min($allCount, $home->products()->count())]])->concat($cats->map(fn ($t) => ['name' => $t['name'], 'slug' => $t['slug'], 'count' => min($t['count'], $allCount)]));
@endphp
@if ($picks->isNotEmpty())
  <section id="fruits" class="wrap" x-data="{ tab: 'all' }" style="padding-top:clamp(40px,5vw,68px);">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:18px; flex-wrap:wrap;">
      <div>
        <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(23px,3vw,32px); letter-spacing:-.02em; margin:0; color:var(--fn-ink);">{{ __('Today\'s Fresh Picks') }}</h2>
        <p style="margin:8px 0 0; color:var(--fn-muted); font-size:15px;">{{ __('Picked before sunrise, priced for the day.') }}</p>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <div style="display:flex; align-items:center; gap:4px; background:#EEF5EF; border:1px solid var(--fn-line); border-radius:999px; padding:5px; overflow-x:auto; max-width:100%; scrollbar-width:none;">
          @foreach ($tabs as $tab)
            <button class="fh-bbcc6b" type="button" @click="tab = @js($tab['slug'])" :aria-pressed="tab === @js($tab['slug'])" :style="tab === @js($tab['slug']) ? { background: '#FFFFFF', color: 'var(--fn-primary-dark)', boxShadow: '0 4px 12px rgba(25,75,48,.14)' } : { background: 'transparent', color: 'var(--fn-muted)', boxShadow: 'none' }" style="flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; border:0; background:{{ $loop->first ? '#FFFFFF' : 'transparent' }}; color:{{ $loop->first ? 'var(--fn-primary-dark)' : 'var(--fn-muted)' }}; box-shadow:{{ $loop->first ? '0 4px 12px rgba(25,75,48,.14)' : 'none' }}; font-weight:700; font-size:14px; padding:8px 8px 8px 16px; border-radius:999px; cursor:pointer; white-space:nowrap; transition:background .15s ease;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true" :style="{ display: tab === @js($tab['slug']) ? 'inline' : 'none' }" style="display:{{ $loop->first ? 'inline' : 'none' }};"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="var(--fn-primary)"></path></svg>
              {{ $tab['name'] }}
              <span :style="tab === @js($tab['slug']) ? { background: 'var(--fn-accent)', color: '#FFFFFF' } : { background: '#FFFFFF', color: 'var(--fn-muted)' }" style="display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:22px; padding:0 7px; border-radius:999px; background:{{ $loop->first ? 'var(--fn-accent)' : '#FFFFFF' }}; color:{{ $loop->first ? '#FFFFFF' : 'var(--fn-muted)' }}; font-size:11.5px; font-weight:800;">{{ $tab['count'] }}</span>
            </button>
          @endforeach
        </div>
        <a class="fh-ed87d8" href="{{ store_route('shop') }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-ink); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px; white-space:nowrap;">
        {{ __('Show All') }}
        <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
      </a>
      </div>
    </div>

    @if ($minis->isNotEmpty())
    <div class="bannerrow" style="margin-top:24px;">
      @foreach ($minis as $i => $b)
        @php $s = $miniStyles[$i % 3]; @endphp
        <a href="{{ $b->href() ?: store_route('pages.show', ['slug' => 'offers']) }}" style="display:block; border-radius:18px; min-height:150px; background:{{ $s['bg'] }};">
          <span style="display:flex; flex-direction:column; justify-content:center; gap:6px; padding:22px 24px; min-height:150px;">
            <span style="font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:{{ $s['kicker'] }};">{{ $b->eyebrow }}</span>
            <strong style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:19px; line-height:1.25; color:{{ $s['title'] }};">{{ $b->title }}</strong>
            <span style="font-size:13.5px; color:{{ $s['body'] }};">{{ $b->text }}</span>
            <span style="margin-top:8px; font-weight:700; font-size:13.5px; color:{{ $s['cta'] }};">{{ $b->button_label ?: __('Shop now') }} →</span>
          </span>
        </a>
      @endforeach
    </div>
    @endif

    <div class="pgrid" style="margin-top:22px;">
      @foreach ($picks as $i => $product)
        @php $slug = $product->categories->first()?->slug; @endphp
        <div class="{{ $i >= $allCount ? 'fn-hide' : '' }}" :class="(tab === 'all' ? {{ $i }} < {{ $allCount }} : tab === @js($slug)) ? '' : 'fn-hide'" style="display:contents;">
        @include('storefront.home.partials.card', ['c' => $home->card($product)])
        </div>
      @endforeach
    </div>
  </section>
@endif
