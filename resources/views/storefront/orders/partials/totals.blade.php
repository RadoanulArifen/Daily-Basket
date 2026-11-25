{{-- Order totals as charged (AED), used on the order page. --}}
<dl class="mt-5 space-y-2 text-[15px]">
    <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('store.cart.subtotal_plain') }}</dt><dd>{{ price_order($order->subtotal, $order) }}</dd></div>
    @foreach ($order->dealLines() as $deal)
        <div class="flex justify-between gap-4 text-brand-red"><dt>{{ __('store.deals.line', ['name' => $deal['label']]) }}</dt><dd>−{{ price_order($deal['amount'], $order) }}</dd></div>
    @endforeach
    @unless ($order->discount_total->isZero())
        <div class="flex justify-between gap-4 text-brand-red"><dt>{{ __('store.discount.line', ['code' => $order->discount_code]) }}</dt><dd>−{{ price_order($order->discount_total, $order) }}</dd></div>
    @endunless
    <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ $order->shipping_method_name }}</dt><dd>{{ $order->shipping_total->isZero() ? __('store.checkout.free') : price_order($order->shipping_total, $order) }}</dd></div>
    @unless ($order->payment_fee->isZero())
        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('store.checkout.cod_fee') }}</dt><dd>{{ price_order($order->payment_fee, $order) }}</dd></div>
    @endunless
    @if (! $order->prices_include_tax && $order->tax_label)
        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ $order->tax_label }}</dt><dd>{{ price_order($order->tax_total, $order) }}</dd></div>
    @endif
</dl>
<div class="mt-4 flex justify-between gap-4 border-t border-line pt-4 text-lg font-semibold">
    <span>{{ __('store.cart.total') }}</span><span>{{ price_order($order->grand_total, $order) }}</span>
</div>
@if ($order->prices_include_tax && $order->tax_label)
    <p class="mt-1 text-end text-sm text-stone">{{ __('store.order.tax_included', ['tax' => $order->tax_label, 'amount' => price_order($order->tax_total, $order)]) }}</p>
@endif
