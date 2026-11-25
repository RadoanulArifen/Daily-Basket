{{-- Shop by Category — Catalog → Categories (top level, in their order, with their pictures). --}}
@php
    $categories = $home->categories();
    $tints = ['#FFE4E1', '#DDF3E2', '#EAE4FF', '#FFEBD6', '#FFE0E8', '#DCEFFF', '#FFF3CC', '#E6F0D8'];
@endphp
@if ($categories->isNotEmpty())
  <section id="shop" class="wrap" style="padding-top:clamp(36px,5vw,64px);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
      <h2 style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(25px,3.2vw,36px); letter-spacing:-.02em; margin:0; color:var(--fn-ink);">{{ __('Shop by Category') }}</h2>
      <a class="fh-ed87d8" href="{{ store_route('shop') }}" style="display:inline-flex; align-items:center; gap:12px; background:var(--fn-ink); color:#FFFFFF; font-weight:700; font-size:14px; padding:6px 6px 6px 18px; border-radius:999px;">
        {{ __('Show All') }}
        <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:#FFFFFF; align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span>
      </a>
    </div>
    <div class="catrow" style="margin-top:24px;">
      @foreach ($categories as $i => $row)
        @php $cat = $row['category']; @endphp
        <a href="{{ store_route('collections.show', ['slug' => $cat->slug]) }}" class="cat-tile fh-f46280" style="display:flex; flex-direction:column; align-items:center; gap:14px; padding:22px 16px 20px; border-radius:22px; background:linear-gradient(180deg, {{ $tints[$i % 8] }} 0%, #FFFFFF 88%); border:1px solid #EEF2EE; text-align:center; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;">
          <span style="position:relative; display:block; width:100%; max-width:150px; aspect-ratio:1/1; border-radius:18px; overflow:hidden; box-shadow:0 10px 22px rgba(25,75,48,.12);">
            @include('storefront.home.partials.img', ['media' => $cat->image, 'alt' => $cat->name, 'sizes' => '150px'])
          </span>
          <span style="display:flex; flex-direction:column; gap:3px;">
            <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15.5px; color:var(--fn-ink); line-height:1.25;">{{ $cat->name }}</span>
            <span class="fn-card-label" style="font-size:12.5px; color:var(--fn-muted);"><x-icon name="package" class="fn-card-label-icon" />{{ $row['count'] }} {{ $row['count'] === 1 ? __('Product') : __('Products') }}</span>
          </span>
        </a>
      @endforeach
    </div>
  </section>
@endif
