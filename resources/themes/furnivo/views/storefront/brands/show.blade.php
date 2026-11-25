@extends('storefront.layouts.app')

{{-- Brand page — markup and styles from the design (site.zip). --}}
@section('furnivo', true)
@section('overHero', true)
@section('title', $brand->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $brand->description), 160))
@if (request()->hasAny(['type', 'reviews', 'page']))
    @section('noindex', true)
@endif

@php
    $hero = $products->first()?->media->first();
    $stars = fn ($rating) => str_repeat('★', (int) round($rating)).str_repeat('☆', 5 - (int) round($rating));
    $availability = $madeToOrder && $inStock ? 'Buy & Made to Order' : ($madeToOrder ? 'Made to Order' : 'Buy');
    $tabLink = fn ($slug) => store_route('brands.show', ['slug' => $brand->slug]).($slug ? '?type='.$slug : '');
@endphp

@section('content')
    <section data-nav-theme="dark" style="position: relative; min-height: 42vh; display: flex; align-items: flex-end; background: rgb(20, 20, 20); overflow: hidden; padding-top: 120px;">
        @if ($hero)
            <div style="position: absolute; inset: 0px;"><img alt="{{ $brand->name }}" src="{{ $hero->url('large') }}" style="width: 100%; height: 100%; object-fit: cover;"></div>
        @endif
        <div style="position: absolute; inset: 0px; background: linear-gradient(rgba(15, 15, 15, 0.55) 0%, rgba(15, 15, 15, 0.25) 42%, rgba(15, 15, 15, 0.88) 100%); pointer-events: none;"></div>
        <div style="position: relative; width: 100%; padding: clamp(48px, 6vw, 72px) clamp(24px, 4.5vw, 80px) clamp(36px, 4vw, 48px); color: rgb(255, 255, 255);">
            <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgba(255, 255, 255, 0.7); margin-bottom: 26px;">
                <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp; <span style="color: rgb(255, 255, 255);" aria-current="page"><span>{{ $brand->name }}</span></span>
            </nav>
            @if ($logo = $brand->logoUrl())
                <div style="background: rgb(255, 255, 255); display: inline-flex; padding: 14px 20px; margin-bottom: 24px;"><img alt="{{ $brand->name }}" src="{{ $logo }}" style="height: 40px; width: auto; max-width: 200px; object-fit: contain; display: block;"></div>
            @endif
            @if ($origin = $brand->origin)
                <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255, 255, 255, 0.75); margin-bottom: 14px;">Maison · <span>{{ $origin }}</span></div>
            @endif
            <h1 style="margin: 0px 0px 18px; font-size: clamp(40px, 5.4vw, 72px); line-height: 1; font-weight: 800; letter-spacing: -0.03em;"><span>{{ $brand->name }}</span></h1>
            @if ($blurb = \App\Support\RichText::text($brand->description))
                <p style="margin: 0px; font-size: clamp(16px, 1.6vw, 20px); line-height: 1.55; color: rgba(255, 255, 255, 0.88); max-width: 60ch;"><span>{{ $blurb }}</span></p>
            @endif
        </div>
    </section>

    <section style="width: 100%; border-bottom: 1px solid rgb(236, 236, 236); background: rgb(250, 249, 247);">
        <div style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px);">
            <div class="brand-facts" style="display: grid; grid-template-columns: repeat(3, 1fr);">
                <div style="padding: 26px 32px 26px 0px; border-right: 1px solid rgb(236, 236, 236);">
                    <div style="font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 8px;">{{ __('store.theme.origin') }}</div>
                    <div style="font-size: 18px; color: rgb(32, 32, 32);"><span>{{ $brand->origin ?: '—' }}</span></div>
                </div>
                <div style="padding: 26px 32px; border-right: 1px solid rgb(236, 236, 236);">
                    <div style="font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 8px;">{{ __('store.theme.in_the_collection') }}</div>
                    <div style="font-size: 18px; color: rgb(32, 32, 32);"><span>{{ $totalPieces }}</span> pieces</div>
                </div>
                <div style="padding: 26px 0px 26px 32px;">
                    <div style="font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: rgb(139, 135, 127); margin-bottom: 8px;">{{ __('store.theme.availability') }}</div>
                    <div style="font-size: 18px; color: rgb(32, 32, 32);">{{ $availability }}</div>
                </div>
            </div>
        </div>
    </section>

    <section style="width: 100%; padding: clamp(48px, 6vw, 80px) clamp(24px, 4.5vw, 80px);">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 28px;">
            <div>
                <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 12px;">{{ __('store.theme.the_collection') }}</div>
                <h2 style="margin: 0px; font-size: clamp(24px, 3vw, 40px); font-weight: 700; letter-spacing: -0.02em;">{{ $chosen?->name ?? 'All' }} <span>{{ $brand->name }}</span> pieces</h2>
            </div>
            <div style="font-size: 13px; letter-spacing: 0.06em; color: rgb(139, 135, 127);"><span>{{ $products->total() }}</span> of <span>{{ $totalPieces }}</span> pieces</div>
        </div>

        @if ($tabs->isNotEmpty())
            <div class="no-sb" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 34px;">
                @foreach (collect([['category' => null, 'count' => $totalPieces]])->concat($tabs) as $tab)
                    @php $on = ($tab['category']?->id ?? null) === ($chosen?->id ?? null); @endphp
                    <a href="{{ $tabLink($tab['category']?->slug) }}" style="cursor: pointer; font-size: 13px; letter-spacing: 0.02em; padding: 9px 16px; transition: box-shadow 200ms, background 200ms, color 200ms; {{ $on ? 'color: rgb(255, 255, 255); background: rgb(29, 29, 29); box-shadow: rgb(29, 29, 29) 0px 0px 0px 1px inset;' : 'color: rgb(32, 32, 32); background: rgb(255, 255, 255); box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset;' }}"><span>{{ $tab['category']?->name ?? 'All' }}</span></a>
                @endforeach
            </div>
        @endif

        @if ($products->isEmpty())
            <p style="margin: 0px; font-size: 16px; color: rgb(102, 102, 102);">{{ __('store.listing.empty_filtered') }}</p>
        @else
            <div class="grid-3" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: clamp(24px, 3vw, 44px) 28px;">
                @foreach ($products as $product)
                    <x-store.product-card :product="$product" />
                @endforeach
            </div>
            @if ($products->hasPages())
                <nav style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: clamp(48px, 6vw, 72px);" aria-label="{{ __('store.theme.pages') }}">
                    @foreach (range(1, $products->lastPage()) as $page)
                        @php $current = $page === $products->currentPage(); @endphp
                        <a href="{{ $products->url($page) }}" class="scp8" @if ($current) aria-current="page" @endif style="min-width: 44px; height: 44px; padding: 0px 12px; border: none; background: rgb(255, 255, 255); font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: box-shadow 220ms, color 220ms; {{ $current ? 'box-shadow: rgb(29, 29, 29) 0px 0px 0px 2px inset; color: rgb(32, 32, 32);' : 'box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; color: rgb(102, 102, 102);' }}"><span>{{ $page }}</span></a>
                    @endforeach
                </nav>
            @endif
        @endif
    </section>

    @if ($reviews->isNotEmpty())
        <section style="width: 100%; background: rgb(245, 244, 240); padding: clamp(48px, 6vw, 80px) clamp(24px, 4.5vw, 80px);">
            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 36px;">
                <div>
                    <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 12px;">{{ __('store.theme.what_clients_say') }}</div>
                    <h2 style="margin: 0px; font-size: clamp(24px, 3vw, 40px); font-weight: 700; letter-spacing: -0.02em;"><span>{{ $brand->name }}</span> reviews</h2>
                </div>
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="font-size: clamp(34px, 4vw, 48px); font-weight: 800; letter-spacing: -0.03em; color: rgb(32, 32, 32);"><span>{{ number_format($reviewAverage, 1) }}</span></div>
                        <div>
                            <div style="font-size: 15px; letter-spacing: 0.14em; color: rgb(29, 29, 29);" aria-hidden="true"><span>{{ $stars($reviewAverage) }}</span></div>
                            <div style="font-size: 13px; color: rgb(139, 135, 127); margin-top: 2px;"><span>{{ $reviewCount }}</span> reviews</div>
                        </div>
                    </div>
                    <div style="position: relative;">
                        <a href="{{ store_route('brands.show', ['slug' => $brand->slug]).'?'.http_build_query(array_filter(array_merge(request()->except('page'), ['reviews' => $reviewSort === 'rating' ? null : 'rating']))) }}"
                           style="cursor: pointer; display: flex; align-items: center; gap: 12px; background: rgb(255, 255, 255); box-shadow: rgb(217, 213, 207) 0px 0px 0px 1px inset; height: 42px; padding: 0px 16px;">
                            <span style="font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(168, 164, 158);">Sort</span>
                            <span style="flex: 1 1 0%; font-size: 14px; color: rgb(32, 32, 32); white-space: nowrap;"><span>{{ $reviewSort === 'rating' ? 'Highest rated' : 'Most recent' }}</span></span>
                            <span style="font-size: 11px; color: rgb(139, 135, 127); transition: transform 250ms;" aria-hidden="true">⌄</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="brand-reviews" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px;">
                @foreach ($reviews as $review)
                    <div style="background: rgb(255, 255, 255); padding: clamp(24px, 3vw, 32px); display: flex; flex-direction: column; gap: 14px;">
                        <div style="font-size: 14px; letter-spacing: 0.14em; color: rgb(29, 29, 29);" role="img" aria-label="{{ $review->rating }} / 5"><span>{{ $stars($review->rating) }}</span></div>
                        <p style="margin: 0px; font-size: 16px; line-height: 26px; color: rgb(51, 51, 51); flex: 1 1 0%;">“<span>{{ $review->body }}</span>”</p>
                        <div style="display: flex; align-items: center; gap: 12px; padding-top: 6px; border-top: 1px solid rgb(241, 239, 236);">
                            <div aria-hidden="true" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: rgb(29, 29, 29); color: rgb(255, 255, 255); display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600; flex-shrink: 0;">{{ mb_substr($review->name, 0, 1) }}</div>
                            <div>
                                <div style="font-size: 14px; font-weight: 600; color: rgb(32, 32, 32);"><span>{{ $review->name }}</span></div>
                                <div style="font-size: 12px; color: rgb(139, 135, 127);"><span>{{ $review->created_at->diffForHumans(['parts' => 1]) }}@if ($review->verified_purchase) · Verified buyer @endif</span></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
