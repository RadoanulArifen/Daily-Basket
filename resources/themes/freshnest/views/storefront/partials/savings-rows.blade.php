{{-- What the shopper saves, line by line: each deal by its name ("Deal: Buy 1, get 1 free")
     and the discount code ("Discount (WELCOME10)") — never an unexplained "Savings" figure.
     Used by the cart page and the cart drawer. Params: $summary, $size (font size, px). --}}
@php $size = $size ?? 15; $show = fn ($m) => \App\Themes\Freshnest\Price::show($m); @endphp
@foreach ($summary->promotions as $deal)
    <div style="display:flex; justify-content:space-between; gap:12px; font-size:{{ $size }}px; color:var(--fn-muted);"><span>{{ __('store.deals.line', ['name' => $deal['name'][app()->getLocale()] ?? $deal['name']['en']]) }}</span><span style="color:var(--fn-primary); font-weight:600; white-space:nowrap;">−{{ $show($deal['amount']) }}</span></div>
@endforeach
@if ($summary->discount && $summary->discountAmount && ! $summary->discountAmount->isZero())
    <div style="display:flex; justify-content:space-between; gap:12px; font-size:{{ $size }}px; color:var(--fn-muted);"><span>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</span><span style="color:var(--fn-primary); font-weight:600; white-space:nowrap;">−{{ $show($summary->discountAmount) }}</span></div>
@elseif ($summary->freeShipping())
    <div style="display:flex; justify-content:space-between; gap:12px; font-size:{{ $size }}px; color:var(--fn-muted);"><span>{{ __('store.discount.line', ['code' => $summary->discount->code]) }}</span><span style="color:var(--fn-primary); font-weight:600;">{{ __('store.discount.free_delivery') }}</span></div>
@endif
