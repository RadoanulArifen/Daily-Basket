{{-- The featured product row, plus any row this design has no separate section for, so a
     shop with its own row names never loses one. --}}
@php
    $claimed = ['new', 'new_in', 'new-in', 'sale', 'offers', 'clearance'];
    $featuredRails = collect($rails)->reject(fn ($r) => in_array($r['key'] ?? '', $claimed, true))->values();
@endphp
@if ($featuredRails->isNotEmpty())
    <section>
        @foreach ($featuredRails as $i => $rail)
            @include('storefront.home.rail', ['rail' => $rail])
        @endforeach
    </section>
@endif
