{{-- The "new_in" product row. Shops name their rows freely, so match on any of the
     names this one is likely to use; anything unmatched is drawn with the featured row. --}}
@php
    $aliases = ['new', 'new_in', 'new-in'];
    $thisRail = collect($rails)->first(fn ($r) => in_array($r['key'] ?? '', $aliases, true));
    @endphp
@if ($thisRail)
    <section>@include('storefront.home.rail', ['rail' => $thisRail])</section>
@endif
