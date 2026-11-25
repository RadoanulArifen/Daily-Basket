@extends('emails.orders.layout')

@section('body')
    <p style="margin:0 0 12px;">{{ __('admin.orders.email_intro', ['number' => $order->number, 'name' => $order->ship_name]) }}</p>
    <p style="margin:0 0 12px;color:#4a4f55;">{{ __("admin.orders.methods.{$order->payment_method}") }} · {{ \App\Support\Countries::name($order->ship_country ?: 'AE') }}</p>
    @include('emails.orders.items')
    <p style="margin:28px 0 0;"><a href="{{ route('admin.orders.show', $order) }}" style="display:inline-block;background:#0f6b5e;color:#fff;text-decoration:none;padding:12px 22px;border-radius:6px;">{{ __('admin.orders.email_open') }}</a></p>
@endsection
