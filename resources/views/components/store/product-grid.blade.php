@props(['products', 'eagerCount' => 0])

<ul {{ $attributes->class('grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 md:grid-cols-3 lg:grid-cols-4') }}>
    @foreach ($products as $product)
        <li><x-store.product-card :product="$product" :eager="$loop->index < $eagerCount" /></li>
    @endforeach
</ul>
