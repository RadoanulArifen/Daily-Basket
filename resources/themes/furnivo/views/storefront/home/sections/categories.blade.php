{{-- Home section: category showcase — site.zip index.html, <!-- CATEGORY SHOWCASE -->.
     The first five top-level categories, in their admin order; .cat-bento lays out 1 large + 4 tiles. --}}
@php
    $categories = ($home ?? app(\App\Themes\Furnivo\HomeData::class))->categories();
@endphp
@if ($categories->isNotEmpty())
    <section style="border-top: 1px solid #ececec; padding: clamp(56px,7vw,96px) 0;" aria-labelledby="fv-home-categories">
        <div style="width: 100%; padding: 0 clamp(24px,4.5vw,80px); display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 44px;">
            <div>
                <div style="font-size: 12px; letter-spacing: .3em; text-transform: uppercase; color: #666666; margin-bottom: 14px;">Collections</div>
                <h2 id="fv-home-categories" style="margin: 0; font-size: clamp(30px,3.6vw,48px); line-height: 1.06; font-weight: 700; letter-spacing: -.025em;">Shop by category</h2>
            </div>
        </div>
        <div style="width: 100%; padding: 0 clamp(24px,4.5vw,80px);">
            <div class="cat-bento fx-category-showcase">
                @foreach ($categories as ['category' => $category, 'count' => $count])
                    <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" class="cat-editorial" style="position: relative; overflow: hidden; cursor: pointer; background: #1a1a1a; display: block;">
                        @if ($category->image)
                            <img src="{{ $category->image->url('large') }}" alt="{{ $category->name }}" loading="lazy" class="cat-ed-img" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .86; transition: transform 800ms cubic-bezier(.22,1,.36,1);">
                        @endif
                        <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,15,15,.28) 0%, rgba(15,15,15,0) 34%, rgba(15,15,15,.72) 100%); pointer-events: none;"></div>
                        <div style="position: absolute; left: 0; right: 0; bottom: 0; padding: 22px; color: #fff; display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; pointer-events: none;">
                            <div>
                                <div style="font-size: clamp(19px,2vw,26px); font-weight: 600; letter-spacing: -.01em; margin-bottom: 5px;">{{ $category->name }}</div>
                                <div style="font-size: 12px; letter-spacing: .16em; text-transform: uppercase; opacity: .8;"><span>{{ $count }}</span> pieces</div>
                            </div>
                            <div class="cat-ed-arrow" aria-hidden="true" style="font-size: 22px; line-height: 1; transition: transform 500ms cubic-bezier(.22,1,.36,1);">→</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
