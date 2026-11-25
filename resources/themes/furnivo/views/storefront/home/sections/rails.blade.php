{{-- Home section: product showcase rails — site.zip index.html, <!-- PRODUCT SHOWCASE RAILS -->.
     Rows: settings furnivo.rails (Online Store → Customize). As in the design:
       - the four explore tiles (settings furnivo.explore) sit directly above the "new" rail;
       - the "best" and "top" rails get the #F5F4F0 band. --}}
@php
    $railsHome = $home ?? app(\App\Themes\Furnivo\HomeData::class);
    $designRails = $rails ?? $railsHome->rails();
    $exploreTiles = $railsHome->explore();
@endphp
@if ($designRails)
    <section style="border-top: 1px solid #ececec;">
        @foreach ($designRails as $rail)
            @if ($rail['key'] === 'new')
                @include('storefront.home.explore', ['tiles' => $exploreTiles])
            @endif
            @include('storefront.home.rail', ['rail' => ['tinted' => in_array($rail['key'], ['best', 'top'], true)] + $rail])
        @endforeach
    </section>
@endif
