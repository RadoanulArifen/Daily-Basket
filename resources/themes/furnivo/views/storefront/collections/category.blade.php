@extends('storefront.layouts.app')

{{-- Category page — markup and styles from the design (site.zip). --}}
@section('furnivo', true)
{{-- The dark hero runs under the header, which stays transparent over it (as in the design). --}}
@section('overHero', true)
@section('title', $category->seo_title ?: $category->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($category->seo_description ?: $category->description)), 160))
@section('canonical', url()->current())

@php
    $catalog = app(\App\Services\Catalog\StorefrontCatalog::class);
    $count = $catalog->inCategory($catalog->products(), $category)->count();
    $highlights = $catalog->inCategory($catalog->products(), $category)->with(['media', 'brandRecord', 'variants'])->limit(8)->get();
    // Each tile: the first piece's photo in that type, or the category's own photo.
    $tiles = $subcategories->map(function ($sub) use ($catalog, $category) {
        $inSub = $catalog->inCategory($catalog->products(), $sub)->with('media');

        return [
            'category' => $sub,
            'count' => (clone $inSub)->count(),
            'image' => (clone $inSub)->first()?->media->first() ?? $category->image,
        ];
    });
    $hero = $category->image;
@endphp

@section('content')
    <section data-nav-theme="dark" style="position: relative; min-height: 56vh; display: flex; align-items: flex-end; overflow: hidden; background: rgb(20, 20, 20); padding-top: 120px;">
        @if ($hero)
            <div style="position: absolute; inset: 0px; background-image: url('{{ $hero->url('large') }}'); background-size: cover; background-position: center center; opacity: 0.62;"></div>
        @endif
        <div style="position: absolute; inset: 0px; background: linear-gradient(rgba(15, 15, 15, 0.55) 0%, rgba(15, 15, 15, 0.15) 38%, rgba(15, 15, 15, 0.85) 100%); pointer-events: none;"></div>
        <div style="position: relative; width: 100%; padding: 0px clamp(24px, 4.5vw, 80px) clamp(46px, 5vw, 68px); color: rgb(255, 255, 255);">
            <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgba(255, 255, 255, 0.75); margin-top: clamp(24px, 3vw, 40px); margin-bottom: 22px;">
                <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp; <span style="color: rgb(255, 255, 255);" aria-current="page"><span>{{ $category->name }}</span></span>
            </nav>
            <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255, 255, 255, 0.72); margin-bottom: 16px;">Collection · <span>{{ $count }}</span> pieces</div>
            <h1 style="margin: 0px 0px 20px; font-size: clamp(44px, 6vw, 84px); line-height: 1; font-weight: 800; letter-spacing: -0.03em;"><span>{{ $category->name }}</span></h1>
            @if ($blurb = \App\Support\RichText::text($category->description))
                <p style="margin: 0px; font-size: clamp(16px, 1.5vw, 19px); line-height: 1.6; color: rgba(255, 255, 255, 0.85); max-width: 56ch;"><span>{{ $blurb }}</span></p>
            @endif
        </div>
    </section>

    @if ($tiles->isNotEmpty())
        <section style="width: 100%; padding: clamp(48px, 6vw, 80px) clamp(24px, 4.5vw, 80px) clamp(30px, 4vw, 48px);">
            <div style="display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 34px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 12px;">{{ __('store.theme.browse') }}</div>
                    <h2 style="margin: 0px; font-size: clamp(24px, 3vw, 38px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.theme.shop') }}<span>{{ $category->name }}</span> by type</h2>
                </div>
                <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}?sort=featured" style="cursor: pointer; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29); white-space: nowrap;">{{ __('store.theme.view_all') }}<span>{{ $category->name }}</span> →</a>
            </div>
            <div class="subtype-cards fx-subcategory-gallery" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(24px, 3vw, 44px) 28px;">
                @foreach ($tiles as $tile)
                    <a class="cat-tile subtype-card" href="{{ store_route('collections.show', ['slug' => $tile['category']->slug]) }}" style="cursor: pointer; display: flex; flex-direction: column;">
                        <div style="position: relative; width: 100%; aspect-ratio: 4 / 3; overflow: hidden; background: rgb(239, 236, 231);">
                            @if ($tile['image'])
                                <img alt="{{ $tile['category']->name }}" class="cat-img" loading="lazy" src="{{ $tile['image']->url('medium') }}" style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: cover; transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);">
                            @endif
                        </div>
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 14px; padding: 18px 2px 0px;">
                            <h3 class="subtype-name" style="margin: 0px; font-size: clamp(19px, 1.7vw, 23px); font-weight: 600; letter-spacing: -0.01em; color: rgb(32, 32, 32); padding-bottom: 3px; box-shadow: rgba(29, 29, 29, 0) 0px -1px 0px inset; transition: box-shadow 300ms;"><span>{{ $tile['category']->name }}</span></h3>
                            <span style="font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(102, 102, 102); white-space: nowrap;"><span>{{ $tile['count'] }}</span> pieces</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($highlights->isNotEmpty())
        <section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(48px, 6vw, 80px) 0px;" x-data="carousel()" aria-labelledby="rail-highlights-title">
            <div style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px);">
                <h2 id="rail-highlights-title" style="margin: 0px 0px 34px; font-size: clamp(24px, 3vw, 36px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.theme.highlights_from') }}<span>{{ $category->name }}</span></h2>
                @include('storefront.home.rail-track', ['rail' => ['key' => 'highlights', 'products' => $highlights]])
            </div>
        </section>
    @endif
@endsection
