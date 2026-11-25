{{-- Promise cards — Content → Banners → "Promise cards at the bottom" (headline + text;
     the icons and colours follow the design's order). --}}
@php
    $cards = $home->banners('trust')->take(4)->values();
    $tcIcons = ['<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.8 14.2 4.4 16.9 4.2 17.8 6.8 20.2 8 19.9 10.7 21.2 13 19.4 15 19.2 17.7 16.5 18.3 14.8 20.4 12 19.6 9.2 20.4 7.5 18.3 4.8 17.7 4.6 15 2.8 13 4.1 10.7 3.8 8 6.2 6.8 7.1 4.2 9.8 4.4Z"></path><path d="m8.8 12.2 2.2 2.2 4.2-4.4"></path></svg>','<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 7h10.5v9H2.5"></path><path d="M13 10h4.2l3.3 3.3V16H13"></path><circle cx="6.5" cy="17" r="1.8"></circle><circle cx="17" cy="17" r="1.8"></circle></svg>','<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.4 7.5 9.5 4.3-1.1 7.5-4.9 7.5-9.5V6Z"></path><rect x="9" y="10.5" width="6" height="5" rx="1"></rect><path d="M10.2 10.5V9a1.8 1.8 0 0 1 3.6 0v1.5"></path></svg>','<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7"></path><path d="M4 4v4h4"></path><path d="m9 12 2 2 4-4"></path></svg>'];
    $tcStyles = ['--c:var(--fn-primary); --d:var(--fn-primary-dark); --t:var(--fn-primary-tint);','--c:var(--fn-accent); --d:var(--fn-accent-dark); --t:var(--fn-accent-tint);','--c:var(--fn-primary); --d:var(--fn-primary-dark); --t:var(--fn-primary-tint);','--c:var(--fn-accent); --d:var(--fn-accent-dark); --t:var(--fn-accent-tint);'];
@endphp
@if ($cards->isNotEmpty())
  <section class="wrap" style="padding-top:clamp(20px,2.4vw,32px);">
    <div class="trust-cards">
      @foreach ($cards as $i => $card)
        <div class="tc fn-icon-card" style="{{ $tcStyles[$i % 4] }}">
          <span class="fn-card-watermark" aria-hidden="true">{!! $tcIcons[$i % 4] !!}</span>
          <span class="tc-ic">{!! $tcIcons[$i % 4] !!}</span>
          <strong class="tc-t">{{ $card->title }}</strong>
          <span class="tc-b">{{ $card->text }}</span>
        </div>
      @endforeach
    </div>
  </section>
@endif
