{{-- The filtered product listing (grid, filters, sort, paging). Kept as its own view so a
     theme can show a different page for a category and still fall back to this. --}}
@extends('storefront.layouts.app')

@section('title', $metaTitle ?? $title)
@php $metaText = $metaDescription ?? \App\Support\RichText::text($description); @endphp
@if (filled($metaText))
    @section('meta_description', \Illuminate\Support\Str::limit(strip_tags($metaText), 160))
@endif
{{-- Filtered and sorted variations shouldn't compete with the main page in Google. --}}
@if (request()->hasAny(['sort', 'min', 'max', 'in_stock', 'sale']))
    @section('noindex', true)
@endif
@section('canonical', url()->current().($products->currentPage() > 1 ? '?page='.$products->currentPage() : ''))

@push('meta')
    @php
        $ldItems = [];
        if ($category?->parent) {
            $ldItems[] = ['label' => $category->parent->name, 'url' => store_route('collections.show', ['slug' => $category->parent->slug])];
        }
        $ldItems[] = ['label' => $title];
        $shareImage = $category?->image ?? $products->first()?->media->first();
    @endphp
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->breadcrumbs($ldItems)) !!}</script>
    @if ($shareImage)<meta property="og:image" content="{{ $shareImage->url('large') }}">@endif
@endpush

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-8 sm:px-6 lg:px-10">
        @php
            $crumbs = [];
            if ($category?->parent) {
                $crumbs[] = ['label' => $category->parent->name, 'url' => store_route('collections.show', ['slug' => $category->parent->slug])];
            }
            $crumbs[] = ['label' => $title];
        @endphp
        <x-store.breadcrumbs :items="$crumbs" />

        <header class="mt-6 max-w-2xl">
            <h1 class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $title }}</h1>
            @if ($description)
                <div class="store-prose mt-3 text-[17px] leading-relaxed text-ink-soft">{!! \App\Support\RichText::render($description) !!}</div>
            @endif
        </header>

        @if ($subcategories->isNotEmpty())
            <nav class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('store.listing.subcategories') }}">
                @foreach ($subcategories as $sub)
                    <a href="{{ store_route('collections.show', ['slug' => $sub->slug]) }}" class="shrink-0 rounded-full border border-line px-4 py-2 text-sm hover:border-ink">{{ $sub->name }}</a>
                @endforeach
            </nav>
        @endif

        @include('storefront.partials.listing')
    </div>
@endsection
