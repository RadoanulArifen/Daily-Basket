@extends('storefront.layouts.app')

{{-- Shop and category pages, from the design (shop.html, fruits.html …). --}}
@section('freshnest', true)
@php
    // "All products" and "New in"/"Sale" take their headline and intro from the page of the same
    // name in Content → Pages (slug shop / new-in / sale; it can stay hidden), else the platform's.
    if (! $category) {
        $slug = request()->routeIs('shop', '*.shop') ? 'shop' : (string) request()->route('slug');
        $named = \App\Models\Page::query()->withoutGlobalScopes()->where('slug', $slug)->first();
        if ($named) {
            $title = $named->title ?: $title;
            $description = $named->intro ?: $description;
        }
    }
@endphp
@section('title', $metaTitle ?? $title)
@if (filled($metaDescription ?? $description ?? null))
    @section('meta_description', \App\Support\RichText::text($metaDescription ?? $description))
@endif

@section('content')
    @include('storefront.partials.page-band', ['title' => $title, 'blurb' => filled($description ?? null) ? \App\Support\RichText::text($description) : null, 'crumbs' => []])
    @include('storefront.collections.listing', [
        'action' => url()->current(),
        'current' => $category?->slug,
    ])
@endsection
