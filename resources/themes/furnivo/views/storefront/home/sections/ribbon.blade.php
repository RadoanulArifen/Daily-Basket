    {{-- Brand marquee (the list is repeated so the loop is seamless) --}}
    @if (($brands = $home->brands())->isNotEmpty())
        <section style="padding: clamp(14px, 1.6vw, 24px) 0px; border-top: 1px solid rgb(236, 236, 236);" aria-label="{{ __('store.theme.our_brands') }}">
            <div class="ser-marquee fx-brand-marquee" style="position: relative; overflow: hidden; mask-image: linear-gradient(90deg, transparent, rgb(0, 0, 0) 8%, rgb(0, 0, 0) 92%, transparent);">
                <div class="ser-marquee-track">
                    @foreach ([0, 1] as $copy)
                        @foreach ($brands as $brand)
                            <a href="{{ store_route('brands.show', ['slug' => $brand->slug]) }}" class="ser-logo-item" title="View {{ $brand->name }}" @if ($copy) aria-hidden="true" tabindex="-1" @endif
                               style="flex: 0 0 auto; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 0px clamp(40px, 5vw, 80px); border-right: 1px solid rgb(236, 236, 236); min-width: 200px;">
                                <div style="display: flex; align-items: center; justify-content: center; height: 96px;"><img alt="{{ $copy ? '' : $brand->name }}" class="ser-brand-logo" src="{{ $brand->logoUrl() }}" style="height: 100%; width: auto; object-fit: contain;"></div>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>
    @endif
