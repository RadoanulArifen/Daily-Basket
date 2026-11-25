@extends('storefront.layouts.app')

{{--
    Furnivo home — sections ported from the design (site.zip, HOME view):
    hero, brands (logo marquee), categories (bento), rails (product rows + explore tiles).
    The other sections are optional and start unticked.
    Order and visibility: Admin → Online Store → Customize.
--}}
@section('overHero', true)
@section('furnivo', true)

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->website()) !!}</script>
    @php $shareImage = app(\App\Themes\Furnivo\HomeData::class)->hero()->first(fn ($slide) => $slide->image)?->image; @endphp
    @if ($shareImage)<meta property="og:image" content="{{ $shareImage->url('large') }}">@endif
@endpush

@section('content')
    @php
        // Shared by the optional sections (Customize → Homepage sections); the design's own
        // four load their data themselves.
        $home = app(\App\Themes\Furnivo\HomeData::class);
        $slides = $home->hero();
        $rails = $home->rails();
        $pad = 'padding: 0px clamp(24px, 4.5vw, 80px)';
    @endphp

    @foreach (app(\App\Services\Theme\ThemeSettings::class)->visibleSections() as $section)
        @include('storefront.home.sections.'.$section)
    @endforeach
@endsection
