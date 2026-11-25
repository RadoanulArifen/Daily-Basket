{{-- A product row with arrows (carousel in js/carousel.js) — markup and styles from the design. --}}
<div style="width: 100%; padding: clamp(48px, 6vw, 76px) clamp(24px, 4.5vw, 80px); background: {{ $rail['tinted'] ? 'rgb(245, 244, 240)' : 'transparent' }};" x-data="carousel()" aria-labelledby="rail-{{ $rail['key'] }}-title">
    <div style="display: flex; align-items: end; justify-content: space-between; margin-bottom: 30px;">
        <div>
            @if ($rail['eyebrow'] !== '')
                <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 12px;"><span>{{ $rail['eyebrow'] }}</span></div>
            @endif
            <h2 id="rail-{{ $rail['key'] }}-title" style="margin: 0px; font-size: clamp(26px, 3vw, 40px); line-height: 1.1; font-weight: 700; letter-spacing: -0.02em;"><span>{{ $rail['title'] }}</span></h2>
        </div>
        <a href="{{ $rail['key'] === 'new' ? store_route('collections.show', ['slug' => 'new-in']) : store_route('shop') }}" style="cursor: pointer; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29); white-space: nowrap;">View all →</a>
    </div>
    @include('storefront.home.rail-track', ['rail' => $rail])
</div>
