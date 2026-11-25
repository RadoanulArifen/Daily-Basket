{{-- A photo filling its box, like the design's image slots. --}}
@if ($media)
<img src="{{ $media->url($size ?? 'medium') }}" srcset="{{ $media->srcset() }}" sizes="{{ $sizes ?? '400px' }}" alt="{{ $alt ?? '' }}" loading="{{ $loading ?? 'lazy' }}" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;">
@elseif (! empty($fallback))
<img src="{{ $fallback }}" alt="{{ $alt ?? '' }}" loading="{{ $loading ?? 'lazy' }}" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;">
@endif
