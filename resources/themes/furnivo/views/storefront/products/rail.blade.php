{{-- Product-page row ("You may also like", "Recently viewed products") — markup from the design. --}}
<section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(48px, 6vw, 80px) 0px;" x-data="carousel()" aria-labelledby="rail-{{ $rail['key'] }}-title">
    <div style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px);">
        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 34px;">
            <h2 id="rail-{{ $rail['key'] }}-title" style="margin: 0px; font-size: clamp(22px, 2.6vw, 32px); font-weight: 700; letter-spacing: -0.02em;">{{ $rail['title'] }}</h2>
            <a href="{{ $rail['url'] }}" style="cursor: pointer; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29); white-space: nowrap;">View all →</a>
        </div>
        @include('storefront.home.rail-track', ['rail' => $rail])
    </div>
</section>
