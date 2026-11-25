{{-- Three offer cards — Content → Banners → "Row of three offer cards" (eyebrow, headline,
     button, link, round picture). --}}
@php
    $row = $home->banners('promo_row')->take(3)->values();
    $bgs = ['#F1F5EC', '#FBEFE6', '#EEF2F6'];
@endphp
@if ($row->isNotEmpty())
  <section class="wrap" style="padding-top:clamp(40px,5vw,64px);">
    <div class="g2-promo3">
      @foreach ($row as $i => $b)
        <a class="fh-72d217" href="{{ $b->href() ?: store_route('shop') }}" style="position:relative; display:flex; flex-direction:column; justify-content:center; gap:8px; min-height:200px; padding:28px; border-radius:20px; overflow:hidden; background:{{ $bgs[$i % 3] }};">
          <span style="position:absolute; right:-10px; top:50%; transform:translateY(-50%); width:48%; aspect-ratio:1/1; border-radius:50%; background:#FFFFFF{{ $b->image ? ' url('.$b->image->url('medium').')' : '' }} center/cover no-repeat; border:6px solid #FFFFFF; box-shadow:0 12px 28px rgba(30,35,40,.12);"></span>
          <span style="position:relative; font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--fn-accent-dark);">{{ $b->eyebrow }}</span>
          <strong style="position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(19px,1.8vw,24px); line-height:1.2; max-width:55%; color:var(--fn-ink);">{{ $b->title }}</strong>
          <span style="position:relative; align-self:flex-start; margin-top:6px; display:inline-flex; align-items:center; height:36px; padding:0 16px; border-radius:999px; background:var(--fn-ink); color:#FFFFFF; font-size:13px; font-weight:700;">{{ $b->button_label ?: __('Shop Now') }}</span>
        </a>
      @endforeach
    </div>
  </section>
@endif
