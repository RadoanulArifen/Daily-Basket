{{-- The four full-height tiles between the product rows — markup and styles from the design. --}}
@if ($tiles)
    <div style="width: 100%; padding: 0px;">
        <div class="explore-grid fx-explore-tiles" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px;">
            @foreach ($tiles as $tile)
                <a href="{{ str_starts_with($tile['url'], '/') ? store_url($tile['url']) : $tile['url'] }}" class="cat-tile" style="position: relative; height: clamp(320px, 32vw, 460px); overflow: hidden; cursor: pointer; background: rgb(233, 230, 225); display: block;">
                    @if ($tile['image'])
                        <div class="cat-img" style="position: absolute; inset: 0px; background-image: url('{{ $tile['image']->url('large') }}'); background-size: cover; background-position: center center; transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);"></div>
                    @endif
                    <div style="position: absolute; inset: 0px; background: linear-gradient(rgba(15, 15, 15, 0.15) 0%, rgba(15, 15, 15, 0) 40%, rgba(15, 15, 15, 0.5) 100%); pointer-events: none;"></div>
                    <div style="position: absolute; left: 0px; right: 0px; bottom: 0px; padding: 26px; display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; pointer-events: none;">
                        <div style="color: rgb(255, 255, 255); font-size: clamp(24px, 2.4vw, 32px); font-weight: 700; letter-spacing: -0.01em; line-height: 1.05;"><span>{{ $tile['label'] }}</span></div>
                        <span aria-hidden="true" style="flex-shrink: 0; width: 46px; height: 46px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, 0.75); display: flex; align-items: center; justify-content: center; color: rgb(255, 255, 255);"><svg fill="none" height="16" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" width="16"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
