{{-- Google Analytics purchase event, shared by the storefront order pages. --}}
@push('meta')
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            window.addEventListener('load', () => window.gtag && gtag('event', 'purchase', @js([
                'transaction_id' => $order->number,
                'value' => (float) $order->grand_total->toDecimal(),
                'tax' => (float) $order->tax_total->toDecimal(),
                'shipping' => (float) $order->shipping_total->toDecimal(),
                'currency' => $order->currency,
                'coupon' => $order->discount_code,
                'items' => $order->items->map(fn ($i) => ['item_id' => $i->sku ?: (string) $i->product_id, 'item_name' => $i->getTranslation('name', 'en'), 'item_variant' => $i->getTranslation('variant_title', 'en', false) ?: null, 'price' => (float) $i->unit_price->toDecimal(), 'quantity' => $i->quantity])->all(),
            ])));
        </script>
    @endpush
