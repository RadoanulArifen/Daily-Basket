{{-- Order totals in the design's style. Amounts are shown as charged, in the order's
     own currency (price_order), not the currency the visitor is browsing in. --}}
@php
    $row = 'display: flex; align-items: baseline; justify-content: space-between; gap: 16px; font-size: 14px; color: rgb(102, 102, 102); margin-bottom: 10px;';
    $amount = 'color: rgb(32, 32, 32); white-space: nowrap;';
@endphp
<div style="{{ $row }}">
    <span>{{ __('store.cart.subtotal_plain') }}</span><span style="{{ $amount }}">{{ price_order($order->subtotal, $order) }}</span>
</div>
@foreach ($order->dealLines() as $deal)
    <div style="{{ $row }}">
        <span>{{ __('store.deals.line', ['name' => $deal['label']]) }}</span><span style="{{ $amount }}">−{{ price_order($deal['amount'], $order) }}</span>
    </div>
@endforeach
@unless ($order->discount_total->isZero())
    <div style="{{ $row }}">
        <span>{{ __('store.discount.line', ['code' => $order->discount_code]) }}</span><span style="{{ $amount }}">−{{ price_order($order->discount_total, $order) }}</span>
    </div>
@endunless
<div style="{{ $row }}">
    <span>{{ $order->shipping_method_name }}</span><span style="{{ $amount }}">{{ $order->shipping_total->isZero() ? __('store.checkout.free') : price_order($order->shipping_total, $order) }}</span>
</div>
@unless ($order->payment_fee->isZero())
    <div style="{{ $row }}">
        <span>{{ __('store.checkout.cod_fee') }}</span><span style="{{ $amount }}">{{ price_order($order->payment_fee, $order) }}</span>
    </div>
@endunless
@if (! $order->prices_include_tax && $order->tax_label)
    <div style="{{ $row }}">
        <span>{{ $order->tax_label }}</span><span style="{{ $amount }}">{{ price_order($order->tax_total, $order) }}</span>
    </div>
@endif
<div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px; font-size: 17px; font-weight: 600; color: rgb(32, 32, 32); border-top: 1px solid rgb(236, 236, 236); padding-top: 16px; margin-top: 6px;">
    <span>{{ __('store.cart.total') }}</span><span style="white-space: nowrap;">{{ price_order($order->grand_total, $order) }}</span>
</div>
@if ($order->prices_include_tax && $order->tax_label)
    <p style="margin: 8px 0px 0px; text-align: end; font-size: 12px; color: rgb(139, 135, 127);">{{ __('store.order.tax_included', ['tax' => $order->tax_label, 'amount' => price_order($order->tax_total, $order)]) }}</p>
@endif
