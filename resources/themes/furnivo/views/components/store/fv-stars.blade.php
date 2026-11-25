{{-- Rating stars + review count, as in the design. --}}
@props(['rating', 'count'])
@php $full = (int) round((float) $rating); @endphp
<div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
    <div style="display: flex; align-items: center; gap: 1px; font-size: 12px; color: rgb(32, 32, 32);" role="img" aria-label="{{ number_format((float) $rating, 1) }} / 5">
        @for ($i = 1; $i <= 5; $i++)<span aria-hidden="true" @if ($i > $full) style="color: rgb(207, 202, 194);" @endif>★</span>@endfor
    </div>
    <span style="font-size: 13px; color: rgb(154, 150, 143);"><span>({{ $count }})</span></span>
</div>
