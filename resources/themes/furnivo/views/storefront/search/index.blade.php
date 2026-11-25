{{-- Search results — the design uses the same filtered grid as a listing. --}}
@php
    $found = $products && $products->total() > 0;
@endphp

{{-- Result pages should not compete with the shop's own pages in search engines. --}}
@section('noindex', true)

@if ($found)
    @include('storefront.collections.listing', [
        'title' => '“'.$term.'”',
        'description' => null,
        'metaTitle' => __('store.search.title_for', ['term' => $term]),
        'metaDescription' => null,
        'category' => null,
        'eyebrow' => 'Search results',
        'crumbs' => [__('store.search.title')],
    ])
@else
    @include('storefront.search.empty', ['term' => $term])
@endif
