{{-- Home section: brand logo marquee — site.zip index.html, <!-- OUR BRAND — logo marquee only -->.
     Brands with a logo (Admin → Products → Brands). The list is drawn twice so the loop is seamless, as in the design. --}}
@php
    $brands = ($home ?? app(\App\Themes\Furnivo\HomeData::class))->brands();
@endphp
@if ($brands->isNotEmpty())
    <section style="padding: clamp(14px,1.6vw,24px) 0; border-top: 1px solid #ececec;" aria-label="Our brands">
        <div class="ser-marquee fx-brand-marquee" style="position: relative; overflow: hidden; -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);">
            <div class="ser-marquee-track">
                @foreach ([0, 1] as $copy)
                    @foreach ($brands as $brand)
                        <a href="{{ store_route('brands.show', ['slug' => $brand->slug]) }}" title="View {{ $brand->name }}" class="ser-logo-item" @if ($copy) aria-hidden="true" tabindex="-1" @endif
                           style="flex: 0 0 auto; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 0 clamp(40px,5vw,80px); border-right: 1px solid #ececec; min-width: 200px;">
                            <div style="display: flex; align-items: center; justify-content: center; height: 96px;">
                                <img src="{{ $brand->logoUrl() }}" alt="{{ $copy ? '' : $brand->name }}" class="ser-brand-logo" style="height: 100%; width: auto; object-fit: contain;">
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>
@endif
