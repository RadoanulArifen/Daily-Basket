{{-- About page — the design's "Our Brand" layout: a dark hero with the first picture, the
     text in a centred column (the first paragraph larger), then two pictures side by side.
     Words: Content → Pages → About (eyebrow, headline, text); pictures: the page's Pictures. --}}
@php
    $images = $page->images();
    $hero = $images->first();
    $pair = $images->slice(1, 2)->values();
@endphp
<div>
    <section data-nav-theme="dark" style="position: relative; min-height: 56vh; display: flex; align-items: center; background: rgb(20, 20, 20); overflow: hidden; padding-top: 120px;">
        @if ($hero)
            <div style="position: absolute; inset: 0px;"><img src="{{ $hero->url('large') }}" srcset="{{ $hero->srcset() }}" sizes="100vw" alt="{{ $hero->alt ?: '' }}" fetchpriority="high" style="width: 100%; height: 100%; object-fit: cover;"></div>
        @endif
        <div style="position: absolute; inset: 0px; background: linear-gradient(90deg, rgba(15, 15, 15, 0.7) 0%, rgba(15, 15, 15, 0.3) 100%); pointer-events: none;"></div>
        <div style="position: relative; width: 100%; padding: 0px clamp(24px, 4.5vw, 80px); color: rgb(255, 255, 255);">
            <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255, 255, 255, 0.8); margin-bottom: 18px;">{{ $page->eyebrow ?: __('store.theme.our_brand') }}</div>
            <h1 style="margin: 0px; max-width: 16ch; font-size: clamp(40px, 5.4vw, 64px); line-height: 1.03; font-weight: 800; letter-spacing: -0.02em;">{{ $page->heading ?: $page->title }}</h1>
        </div>
    </section>

    @if (filled($page->intro) || filled($page->body))
        <section class="fv-about" style="max-width: 820px; margin: 0px auto; padding: clamp(60px, 8vw, 110px) clamp(24px, 4.5vw, 80px);">
            @if (filled($page->intro))
                <p class="fv-about-lead">{{ $page->intro }}</p>
            @endif
            {!! $page->html() !!}
        </section>
    @endif

    @if ($pair->isNotEmpty())
        <section style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px) clamp(60px, 8vw, 110px);">
            <div class="two-col fx-split-section" style="display: grid; grid-template-columns: {{ $pair->count() > 1 ? '1fr 1fr' : '1fr' }}; gap: 2px;">
                @foreach ($pair as $image)
                    <div style="position: relative; aspect-ratio: 4 / 5; overflow: hidden; background: rgb(239, 236, 231);"><img src="{{ $image->url('large') }}" srcset="{{ $image->srcset() }}" sizes="(max-width: 900px) 100vw, 50vw" alt="{{ $image->alt ?: '' }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;"></div>
                @endforeach
            </div>
        </section>
    @endif
</div>
