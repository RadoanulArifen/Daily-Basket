{{-- Furnivo: a top-level category with nothing filtered gets the design's landing page
     (hero, shop by type, highlights). Everything else is the listing. --}}
@php
    $filterKeys = array_merge(
        ['sort', 'min', 'max', 'in_stock', 'sale', 'page', 'q', 'band', 'availability', 'brand', 'colour'],
        array_keys(\App\Services\Catalog\ProductFacets::GROUPS),
    );
    $isLanding = ($category ?? null) && $subcategories->isNotEmpty() && ! request()->hasAny($filterKeys);
@endphp

@if ($isLanding)
    @include('storefront.collections.category', ['category' => $category, 'subcategories' => $subcategories])
@else
    @include('storefront.collections.listing')
@endif
