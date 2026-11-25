@extends('emails.orders.layout')

@section('body')
    @php $url = app(\App\Support\Localization::class)->route('orders.show', ['token' => $order->access_token], $order->locale); @endphp
    <p style="margin:0 0 12px;white-space:pre-line;">{{ $intro }}</p>
    @if ($event === 'shipped' && $order->tracking_number)
        <p style="margin:0 0 12px;padding:12px 14px;background:#f3eee6;">{{ __('store.order.tracking', ['carrier' => $order->carrier ?: '—', 'number' => $order->tracking_number]) }}</p>
    @endif
    @include('emails.orders.items')
    <p style="margin:28px 0 0;"><a href="{{ $url }}" style="display:inline-block;background:#22262a;color:#fcfbf9;text-decoration:none;padding:12px 22px;border-radius:6px;">{{ __('store.email.view_order') }}</a></p>
@endsection
