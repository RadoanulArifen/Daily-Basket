{{-- The scrolling product row with arrows (needs x-data="carousel()" on a parent). --}}
<div style="position: relative;">
    <button type="button" aria-label="{{ __('store.theme.previous') }}" class="rail-arrow scp6" @click="move(-1)" :disabled="atStart" style="position: absolute; left: clamp(-84px, -5vw, -48px); top: 38%; transform: translateY(-50%); z-index: 5; width: 56px; height: 56px; border: none; background: transparent; color: rgb(29, 29, 29); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: opacity 250ms;"><svg fill="none" height="42" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" viewBox="0 0 24 24" width="42" aria-hidden="true"><path d="M15 4l-8 8 8 8"></path></svg></button>
    <button type="button" aria-label="{{ __('store.theme.next') }}" class="rail-arrow scp6" @click="move(1)" :disabled="atEnd" style="position: absolute; right: clamp(-84px, -5vw, -48px); top: 38%; transform: translateY(-50%); z-index: 5; width: 56px; height: 56px; border: none; background: transparent; color: rgb(29, 29, 29); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: opacity 250ms;"><svg fill="none" height="42" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" viewBox="0 0 24 24" width="42" aria-hidden="true"><path d="M9 4l8 8-8 8"></path></svg></button>
    <div class="no-sb fv-row" id="rail-{{ $rail['key'] }}" x-ref="track" @scroll.debounce.100ms="update()" style="display: grid; grid-auto-flow: column; grid-auto-columns: clamp(240px, 26vw, 300px); gap: 28px; overflow-x: auto; padding-bottom: 8px; scroll-snap-type: x mandatory;">
        @foreach ($rail['products'] as $product)
            <div style="scroll-snap-align: start;"><x-store.product-card :product="$product" /></div>
        @endforeach
    </div>
</div>
