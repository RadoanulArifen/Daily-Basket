@extends('storefront.layouts.app')

{{--
    Home page. The hero can be replaced from Admin → Content → Banners; the product
    sections fill themselves from featured and newest products.
--}}

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->website()) !!}</script>
    @php $shareImage = $heroBanner?->image ?? $featured->first(fn ($p) => $p->media->isNotEmpty())?->media->first(); @endphp
    @if ($shareImage)<meta property="og:image" content="{{ $shareImage->url('large') }}">@endif
@endpush

@section('content')
    @php
        $heroProduct = $featured->first(fn ($p) => $p->media->isNotEmpty());
        $sceneHero = asset('images/demo/scene-hero.webp');
        $sceneStory = asset('images/demo/scene-story.webp');
    @endphp

    {{-- Sections in the order chosen in Admin → Online Store → Customize. --}}
    @foreach (app(\App\Services\Theme\ThemeSettings::class)->visibleSections() as $section)
        @include('storefront.home.sections.'.$section)
    @endforeach
@endsection
