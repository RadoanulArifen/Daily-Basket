{{-- Home page section: new_in (order and visibility: Admin → Online Store → Customize) --}}
@if ($newest->isNotEmpty())
    @include('storefront.home._product-row', ['id' => 'new', 'eyebrow' => __('store.home.new_eyebrow'), 'title' => __('store.home.new_in'), 'products' => $newest, 'url' => store_route('collections.show', ['slug' => 'new-in'])])
@endif
