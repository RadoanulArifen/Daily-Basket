{{-- Home page section: featured (order and visibility: Admin → Online Store → Customize) --}}
@if ($featured->isNotEmpty())
    @include('storefront.home._product-row', ['id' => 'featured', 'eyebrow' => __('store.home.featured_eyebrow'), 'title' => __('store.home.featured'), 'products' => $featured, 'url' => store_route('shop')])
@endif
