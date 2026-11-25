{{-- Price as the design shows it: bold symbol + whole amount ("$ 24,500"); cents only if any. --}}
@props(['money'])
@php
    $currencies = app(\App\Services\Currency\CurrencyService::class);
    $shown = $currencies->convert($money);
    $symbol = $currencies->current()->symbol ?: $shown->currency;
    $decimal = $shown->toDecimal();
    $amount = str_ends_with($decimal, '.00') ? number_format((float) $decimal) : number_format((float) $decimal, 2);
@endphp
<span {{ $attributes }}><span style="font-weight: 600; letter-spacing: 0.02em;">{{ $symbol }}</span><span><span>{{ $amount }}</span></span></span>
