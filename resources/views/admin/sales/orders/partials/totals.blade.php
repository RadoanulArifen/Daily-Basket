{{-- Order totals: items, deals, discount, delivery, fees, VAT, total. --}}
<dl class="space-y-2 text-sm">
    <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ trans_choice('admin.orders.subtotal', $order->itemCount(), ['count' => $order->itemCount()]) }}</dt><dd dir="ltr">{{ price_admin($order->subtotal) }}</dd></div>
    @foreach ($order->dealLines() as $deal)
        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('store.deals.line', ['name' => $deal['label']]) }}</dt><dd dir="ltr">−{{ price_admin($deal['amount']) }}</dd></div>
    @endforeach
    @if ($order->discount_code)
        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('admin.orders.discount_line', ['code' => $order->discount_code]) }}</dt><dd dir="ltr">−{{ price_admin($order->discount_total) }}</dd></div>
    @endif
    <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('admin.orders.shipping_line', ['method' => $order->shipping_method_name]) }}</dt><dd dir="ltr">{{ price_admin($order->shipping_total) }}</dd></div>
    @unless ($order->payment_fee->isZero())
        <div class="flex justify-between gap-4"><dt class="text-ink-soft">{{ __('admin.orders.cod_fee') }}</dt><dd dir="ltr">{{ price_admin($order->payment_fee) }}</dd></div>
    @endunless
    @if ($order->tax_label)
        <div class="flex justify-between gap-4 text-muted"><dt>{{ $order->prices_include_tax ? __('admin.orders.tax_included', ['label' => $order->tax_label, 'rate' => rtrim(rtrim($order->tax_rate, '0'), '.')]) : $order->tax_label.' ('.rtrim(rtrim($order->tax_rate, '0'), '.').'%)' }}</dt><dd dir="ltr">{{ price_admin($order->tax_total) }}</dd></div>
    @endif
    <div class="flex justify-between gap-4 border-t border-line pt-2 text-[15px] font-semibold"><dt>{{ __('admin.orders.fields.total') }}</dt><dd dir="ltr">{{ price_admin($order->grand_total) }}</dd></div>
</dl>