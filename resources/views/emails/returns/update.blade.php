@extends('emails.orders.layout')

@section('body')
    @php $url = app(\App\Support\Localization::class)->route('orders.show', ['token' => $order->access_token], $order->locale); @endphp
    <p style="margin:0 0 12px;">{{ __('store.email.hello', ['name' => \Illuminate\Support\Str::before($order->ship_name, ' ')]) }}</p>
    <p style="margin:0 0 12px;">{{ __("store.returns.email.{$step}_text", ['number' => $return->number, 'order' => $order->number]) }}</p>
    @if ($return->admin_note && in_array($step, ['approved', 'rejected', 'completed'], true))
        <p style="margin:0 0 12px;padding:12px 14px;background:#f3eee6;white-space:pre-line;">{{ $return->admin_note }}</p>
    @endif
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e6e1d8;margin-top:8px;">
        @foreach ($return->items as $item)
            <tr><td style="padding:10px 0;border-bottom:1px solid #e6e1d8;">{{ $item->orderItem->name }}@if ($item->orderItem->variant_title)<br><span style="color:#7d776c;font-size:13px;">{{ $item->orderItem->variant_title }}</span>@endif</td><td style="padding:10px 0;border-bottom:1px solid #e6e1d8;white-space:nowrap;">× {{ $item->quantity }}</td></tr>
        @endforeach
    </table>
    <p style="margin:28px 0 0;"><a href="{{ $url }}" style="display:inline-block;background:#22262a;color:#fcfbf9;text-decoration:none;padding:12px 22px;border-radius:6px;">{{ __('store.email.view_order') }}</a></p>
@endsection
