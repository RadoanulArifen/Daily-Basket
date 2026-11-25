@props(['rating', 'size' => 'size-4'])
{{-- Five stars, filled to the nearest half. --}}
@php $rounded = round(((float) $rating) * 2) / 2; @endphp
<span {{ $attributes->class('inline-flex items-center text-brand-red') }} role="img" aria-label="{{ __('store.reviews.stars', ['rating' => rtrim(rtrim(number_format((float) $rating, 1), '0'), '.')]) }}">
    @for ($i = 1; $i <= 5; $i++)
        <svg viewBox="0 0 24 24" class="{{ $size }} rtl:-scale-x-100" aria-hidden="true">
            @if ($rounded >= $i)
                <path fill="currentColor" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 17.3l-5.9 3.2 1.3-6.5L2.5 9.4l6.6-.8z"/>
            @elseif ($rounded >= $i - 0.5)
                <defs><clipPath id="half-{{ $i }}"><rect x="0" y="0" width="12" height="24"/></clipPath></defs>
                <path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 17.3l-5.9 3.2 1.3-6.5L2.5 9.4l6.6-.8z"/>
                <path fill="currentColor" clip-path="url(#half-{{ $i }})" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 17.3l-5.9 3.2 1.3-6.5L2.5 9.4l6.6-.8z"/>
            @else
                <path fill="none" stroke="currentColor" stroke-width="1.5" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 17.3l-5.9 3.2 1.3-6.5L2.5 9.4l6.6-.8z"/>
            @endif
        </svg>
    @endfor
</span>
