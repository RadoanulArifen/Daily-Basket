{{--
    Tax invoice. A self-contained page designed for printing (browser → Print → Save as PDF),
    so no PDF library is needed. Shared by the admin and the customer's order page.
--}}
@php
    $localization = app(\App\Support\Localization::class);
    $s = settings();
    $store = $s->trans('general.store_name') ?? config('app.name');
    $trn = $s->get('general.trn');
    $isTaxInvoice = $order->tax_label && (float) $order->tax_rate > 0;
    $rate = rtrim(rtrim($order->tax_rate, '0'), '.');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $localization->direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @include('partials.favicons')
    <title>{{ __('store.invoice.title') }} {{ $order->number }} — {{ $store }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Tahoma, Arial, sans-serif; color: #22262a; background: #f2f3f1; }
        .page { max-width: 820px; margin: 24px auto; background: #fff; padding: 48px; }
        h1 { margin: 0; font-size: 26px; letter-spacing: -.01em; }
        .muted { color: #6b7077; }
        .row { display: flex; justify-content: space-between; gap: 32px; flex-wrap: wrap; }
        .band { height: 8px; margin: -48px -48px 40px; background: repeating-linear-gradient(135deg, #9b2c24 0 8px, #22262a 8px 16px, #e8dfd0 16px 24px); }
        table { width: 100%; border-collapse: collapse; margin-top: 32px; }
        th { text-align: start; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7077; border-bottom: 2px solid #22262a; padding: 8px 6px; }
        td { border-bottom: 1px solid #e6e1d8; padding: 10px 6px; vertical-align: top; }
        .num { text-align: end; white-space: nowrap; }
        .totals { margin-inline-start: auto; width: 320px; max-width: 100%; margin-top: 16px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 6px; }
        .totals .grand { border-top: 2px solid #22262a; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16px; }
        .actions { max-width: 820px; margin: 16px auto 0; text-align: end; }
        .actions button { font: inherit; padding: 8px 16px; border: 0; border-radius: 6px; background: #22262a; color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .page { margin: 0; padding: 24px; max-width: none; } .band { margin: -24px -24px 24px; } .actions { display: none; } }
        @media (max-width: 600px) { .page { padding: 24px; margin: 0; } .band { margin: -24px -24px 24px; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" data-print>{{ __('store.invoice.print') }}</button></div>
    <main class="page">
        <div class="band"></div>
        <div class="row">
            <div>
                <h1>{{ $isTaxInvoice ? __('store.invoice.tax_invoice') : __('store.invoice.title') }}</h1>
                <p class="muted" style="margin:4px 0 0">{{ __('store.invoice.number', ['number' => $order->number]) }} · {{ $order->placed_at->timezone(config('store.display_timezone'))->translatedFormat('j F Y') }}</p>
            </div>
            <div style="text-align:end">
                <strong>{{ $store }}</strong><br>
                @if ($address = $s->trans('general.address'))<span class="muted" style="white-space:pre-line">{{ $address }}</span><br>@endif
                @if ($trn)<span>{{ __('store.invoice.trn') }}: <span dir="ltr">{{ $trn }}</span></span>@endif
            </div>
        </div>

        <div class="row" style="margin-top:32px">
            <div>
                <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em">{{ __('store.invoice.bill_to') }}</div>
                {{ $order->ship_name }}<br>
                {{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>
                {{ $order->shipRegionLine() }}<br>
                <span dir="ltr">{{ $order->phone }}</span> · {{ $order->email }}
            </div>
            <div style="text-align:end">
                <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.04em">{{ __('store.checkout.payment') }}</div>
                {{ __("store.order.methods.{$order->payment_method}") }}<br>
                {{ __("store.order.payment_status.{$order->payment_status->value}") }}
            </div>
        </div>

        <table>
            <thead><tr><th>{{ __('store.invoice.item') }}</th><th class="num">{{ __('store.invoice.qty') }}</th><th class="num">{{ __('store.invoice.unit') }}</th><th class="num">{{ __('store.invoice.amount') }}</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}@if ($item->variant_title)<br><span class="muted">{{ $item->variant_title }}@if ($item->optionsLabel()) · {{ $item->optionsLabel() }}@endif</span>@endif @if ($item->sku)<br><span class="muted" dir="ltr">{{ $item->sku }}</span>@endif</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ price_order($item->unit_price) }}</td>
                        <td class="num">{{ price_order($item->line_total) }}</td>
                    </tr>
                @endforeach
                @foreach ($order->dealLines() as $deal)
                    <tr><td>{{ __('store.deals.line', ['name' => $deal['label']]) }}</td><td class="num">1</td><td class="num">−{{ price_order($deal['amount']) }}</td><td class="num">−{{ price_order($deal['amount']) }}</td></tr>
                @endforeach
                @unless ($order->discount_total->isZero())
                    <tr><td>{{ __('store.discount.line', ['code' => $order->discount_code]) }}</td><td class="num">1</td><td class="num">−{{ price_order($order->discount_total) }}</td><td class="num">−{{ price_order($order->discount_total) }}</td></tr>
                @endunless
                <tr><td>{{ $order->shipping_method_name }}</td><td class="num">1</td><td class="num">{{ price_order($order->shipping_total) }}</td><td class="num">{{ price_order($order->shipping_total) }}</td></tr>
                @unless ($order->payment_fee->isZero())
                    <tr><td>{{ __('store.checkout.cod_fee') }}</td><td class="num">1</td><td class="num">{{ price_order($order->payment_fee) }}</td><td class="num">{{ price_order($order->payment_fee) }}</td></tr>
                @endunless
            </tbody>
        </table>

        <div class="totals">
            @if ($isTaxInvoice)
                @php $net = $order->grand_total->subtract($order->tax_total); @endphp
                <div><span>{{ __('store.invoice.net') }}</span><span>{{ price_order($net) }}</span></div>
                <div><span>{{ $order->tax_label }} ({{ $rate }}%)</span><span>{{ price_order($order->tax_total) }}</span></div>
            @endif
            <div class="grand"><span>{{ __('store.invoice.total', ['currency' => $order->currency]) }}</span><span>{{ price_order($order->grand_total) }}</span></div>
        </div>

        @if ($isTaxInvoice && $order->prices_include_tax)
            <p class="muted" style="margin-top:24px;font-size:12px">{{ __('store.invoice.inclusive_note', ['tax' => $order->tax_label]) }}</p>
        @endif
        @if ($isTaxInvoice && ! $trn)
            <p style="margin-top:8px;font-size:12px;color:#b42318">{{ __('store.invoice.trn_missing') }}</p>
        @endif
    </main>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.querySelector('[data-print]').addEventListener('click', () => window.print());</script>
</body>
</html>
