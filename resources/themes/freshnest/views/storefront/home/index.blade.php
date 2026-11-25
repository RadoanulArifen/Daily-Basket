@extends('storefront.layouts.app')

{{--
    FreshNest home — every section from the design (freshnest-html, home), in the design's
    order. Order and visibility: Admin → Online Store → Customize. Each section reads its
    words and pictures from Content → Banners (by "Where"), the catalogue and Marketing.
--}}
@section('freshnest', true)

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->website()) !!}</script>
@endpush

@section('content')
    @php $home = app(\App\Themes\Freshnest\HomeData::class); @endphp
    @foreach (app(\App\Services\Theme\ThemeSettings::class)->visibleSections() as $section)
        @includeIf('storefront.home.sections.'.$section)
    @endforeach
@endsection
