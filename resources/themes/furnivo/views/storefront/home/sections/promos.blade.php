{{-- Promotion tiles: the "explore" strip from Online Store → Customize. --}}
@if ($tiles = $home->explore())
    @include('storefront.home.explore', ['tiles' => $tiles])
@endif
