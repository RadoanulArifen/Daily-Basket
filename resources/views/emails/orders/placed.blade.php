@extends('emails.orders.layout')

@section('body')
    @php $url = app(\App\Support\Localization::class)->route('orders.show', ['token' => $order->access_token], $order->locale); @endphp
    @section('preview', __('store.email.order_preview', ['number' => $order->number]))

    <p style="margin:0 0 12px;">{{ __('store.email.hello', ['name' => \Illuminate\Support\Str::before($order->ship_name, ' ')]) }}</p>
    <p style="margin:0 0 12px;">{{ $order->awaitsOnlinePayment() ? __('store.email.order_received_unpaid', ['number' => $order->number]) : $intro }}</p>
    @if ($order->payment_method === 'cod')
        <p style="margin:0 0 12px;">{{ __('store.email.cod_note', ['total' => price_order($order->grand_total)]) }}</p>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:22px 0 4px;background:#faf9f7;border:1px solid #f0ede8;">
        <tr>
            <td style="padding:14px 18px;font-size:13px;color:#8b877f;">
                {{ __('store.order.number_short') }}<br>
                <span style="font-size:15px;color:#1d1d1d;font-weight:bold;">{{ $order->number }}</span>
            </td>
            <td style="padding:14px 18px;font-size:13px;color:#8b877f;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">
                {{ __('store.order.placed') }}<br>
                <span style="font-size:15px;color:#1d1d1d;">{{ $order->placed_at?->translatedFormat('j M Y') }}</span>
            </td>
        </tr>
    </table>

    @include('emails.orders.items')

    <p style="margin:20px 0 4px;font-weight:bold;">{{ __('store.checkout.address') }}</p>
    <p style="margin:0;color:#4a4f55;">{{ $order->ship_name }}<br>{{ $order->ship_address_line }}@if ($order->ship_building), {{ $order->ship_building }}@endif<br>{{ $order->shipRegionLine() }}</p>

    <p style="margin:28px 0 0;"><a href="{{ $url }}" style="display:inline-block;background:#22262a;color:#fcfbf9;text-decoration:none;padding:12px 22px;border-radius:6px;">{{ __('store.email.view_order') }}</a></p>
@endsection
