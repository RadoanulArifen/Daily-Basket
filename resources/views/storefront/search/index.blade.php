@extends('storefront.layouts.app')

@section('title', $term ? __('store.search.title_for', ['term' => $term]) : __('store.search.title'))
@section('noindex', true)

@section('content')
    <div class="mx-auto max-w-shop px-4 pb-20 pt-10 sm:px-6 lg:px-10">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('store.search.title') }}</h1>

        <form method="GET" action="{{ store_route('search') }}" role="search" class="mt-6 flex max-w-2xl gap-2">
            <label for="search-page-q" class="sr-only">{{ __('store.search.label') }}</label>
            <input id="search-page-q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('store.search.placeholder') }}" class="store-field" autocomplete="off" @if (! $term) autofocus @endif>
            <x-store.button class="shrink-0"><x-icon name="search" class="size-5" /><span class="max-sm:sr-only">{{ __('store.search.submit') }}</span></x-store.button>
        </form>

        @if ($products === null)
            @if ($term !== '')
                <p class="mt-6 text-[15px] text-ink-soft">{{ __('store.search.too_short', ['min' => \App\Http\Controllers\Storefront\SearchController::MIN_LENGTH]) }}</p>
            @endif
        @elseif ($products->total() === 0 && ! ($filters['in_stock'] || $filters['sale'] || $filters['min'] !== null || $filters['max'] !== null))
            <div class="mt-10 max-w-2xl">
                <p class="text-lg font-medium">{{ __('store.search.no_results', ['term' => $term]) }}</p>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('store.search.no_results_text') }}</p>
                <x-store.button :href="store_route('shop')" variant="secondary" class="mt-6">{{ __('store.listing.browse_all') }}</x-store.button>
            </div>
        @else
            <p class="mt-6 text-[15px] text-ink-soft" role="status">{{ trans_choice('store.search.results', $products->total(), ['count' => $products->total(), 'term' => $term]) }}</p>
            @include('storefront.partials.listing', ['keep' => ['q' => $term]])
        @endif
    </div>
@endsection
