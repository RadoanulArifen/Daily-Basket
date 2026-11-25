{{-- The green band at the top of inner pages: breadcrumb, title and intro (from the design). --}}
  <div style="background:var(--fn-primary-tint); border-bottom:1px solid var(--fn-line);">
    <div class="wrap" style="padding-top:22px; padding-bottom:26px;">
      <nav aria-label="{{ __('Breadcrumb') }}" style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--fn-muted);">
        <a href="{{ store_route('home') }}">{{ __('Home') }}</a>
        @foreach ($crumbs ?? [] as [$label, $href])
        <span>/</span>
        <a href="{{ $href }}">{{ $label }}</a>
        @endforeach
        <span>/</span>
        <span style="color:var(--fn-ink); font-weight:600;" aria-current="page">{{ $crumb ?? $title }}</span>
      </nav>
      <h1 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(28px,4vw,42px); letter-spacing:-.025em; margin:12px 0 0; color:var(--fn-ink);">{{ $title }}</h1>
      @if (filled($blurb ?? null))
      <p style="margin:8px 0 0; font-size:15.5px; color:var(--fn-muted); max-width:620px; line-height:1.6; text-wrap:pretty;">{{ $blurb }}</p>
      @endif
    </div>
  </div>
