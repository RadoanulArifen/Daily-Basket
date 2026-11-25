@extends('storefront.layouts.app')

@section('title', $brand->name)

@section('content')
    <div class="mx-auto max-w-shop px-4 py-12 sm:px-6 lg:px-10">
        <h1 class="text-3xl font-semibold">{{ $brand->name }}</h1>
        @if ($brand->description)<div class="store-prose mt-2 text-ink-soft">{!! \App\Support\RichText::render($brand->description) !!}</div>@endif
        <ul class="mt-8 grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)<li><x-store.product-card :product="$product" /></li>@endforeach
        </ul>
        {{ $products->links() }}
    </div>
@endsection
