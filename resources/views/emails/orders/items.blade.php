<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e6e1d8;margin-top:16px;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #e6e1d8;">{{ $item->name }}@if ($item->variant_title)<br><span style="color:#7d776c;font-size:13px;">{{ $item->variant_title }}@if ($item->optionsLabel()) · {{ $item->optionsLabel() }}@endif</span>@endif</td>
            <td style="padding:10px 8px;border-bottom:1px solid #e6e1d8;white-space:nowrap;color:#4a4f55;">× {{ $item->quantity }}</td>
            <td style="padding:10px 0;border-bottom:1px solid #e6e1d8;white-space:nowrap;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">{{ price_order($item->line_total) }}</td>
        </tr>
    @endforeach
    @foreach ($order->dealLines() as $deal)
        <tr><td style="padding:8px 0;color:#9b2c24;">{{ __('store.deals.line', ['name' => $deal['label']]) }}</td><td></td><td style="padding:8px 0;color:#9b2c24;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">−{{ price_order($deal['amount']) }}</td></tr>
    @endforeach
    @unless ($order->discount_total->isZero())
        <tr><td style="padding:8px 0;color:#9b2c24;">{{ __('store.discount.line', ['code' => $order->discount_code]) }}</td><td></td><td style="padding:8px 0;color:#9b2c24;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">−{{ price_order($order->discount_total) }}</td></tr>
    @endunless
    <tr><td style="padding:8px 0;color:#4a4f55;">{{ $order->shipping_method_name }}</td><td></td><td style="padding:8px 0;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">{{ $order->shipping_total->isZero() ? __('store.checkout.free') : price_order($order->shipping_total) }}</td></tr>
    @unless ($order->payment_fee->isZero())
        <tr><td style="padding:4px 0;color:#4a4f55;">{{ __('store.checkout.cod_fee') }}</td><td></td><td style="padding:4px 0;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">{{ price_order($order->payment_fee) }}</td></tr>
    @endunless
    <tr><td style="padding:10px 0;font-weight:bold;border-top:2px solid #22262a;">{{ __('store.cart.total') }}</td><td style="border-top:2px solid #22262a;"></td><td style="padding:10px 0;font-weight:bold;border-top:2px solid #22262a;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">{{ price_order($order->grand_total) }}</td></tr>
</table>
@if ($order->tax_label && $order->prices_include_tax)
    <p style="font-size:13px;color:#7d776c;margin:6px 0 0;">{{ __('store.order.tax_included', ['tax' => $order->tax_label, 'amount' => price_order($order->tax_total)]) }}</p>
@endif
