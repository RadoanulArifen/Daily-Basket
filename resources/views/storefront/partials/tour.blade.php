{{--
    The shopper tour (App\Services\Tours\StorefrontTour): the data the popups read. The tour runs
    in resources/js/storefront/tour.js and keeps its progress in the shopper's browser.
    Online Store → Preferences can switch it off, and then nothing is printed.
--}}
@php $storeTour = \App\Services\Tours\StorefrontTour::payload(); @endphp
@if ($storeTour && $storeTour['steps'] !== [])
    <script type="application/json" id="store-tour-data">@json($storeTour)</script>
@endif
