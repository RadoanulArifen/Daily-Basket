@extends('emails.orders.layout')

@section('body')
    <p style="margin:0 0 12px;"><strong>{{ $data['name'] }}</strong> &lt;{{ $data['email'] }}&gt;@if (! empty($data['phone'])) · {{ $data['phone'] }}@endif</p>
    <p style="margin:0;white-space:pre-line;">{{ $data['message'] }}</p>
@endsection
