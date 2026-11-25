@extends('storefront.layouts.app')

{{-- Search results, in the design's listing (the design has no search page of its own). --}}
@section('freshnest', true)
@section('title', $term ? __('store.search.title_for', ['term' => $term]) : __('store.search.title'))
@section('noindex', true)

@section('content')
    @include('storefront.partials.page-band', ['title' => $term ? __('Results for “').$term.'”' : __('Search'), 'crumb' => __('Search'),
        'blurb' => $term ? null : 'Type at least two letters in the search box above.'])
    @if ($products)
        @include('storefront.collections.listing', ['action' => store_route('search'), 'current' => null,
            'emptyText' => __('Nothing matched “:term”. Try another word, or browse the aisles on the left.', ['term' => $term])])
    @endif
@endsection
