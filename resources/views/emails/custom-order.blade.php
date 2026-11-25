@extends('emails.orders.layout')

@section('body')
    <p style="margin:0 0 12px;"><strong>{{ $request->name }}</strong> &lt;{{ $request->email }}&gt;@if ($request->phone) · {{ $request->phone }}@endif</p>

    <table role="presentation" style="margin:0 0 16px;font-size:14px;">
        @foreach ([
            __('store.custom.piece') => $request->product?->name,
            __('store.custom.category') => $request->category,
            __('store.custom.dimensions') => $request->dimensions,
            __('store.custom.material') => $request->material,
            __('store.custom.finish') => $request->finish,
            __('store.custom.quantity') => $request->quantity,
            __('store.custom.budget') => $request->budget,
            __('store.custom.timeline') => $request->timeline,
        ] as $label => $value)
            @if (filled($value))
                <tr><td style="padding:2px 14px 2px 0;color:#666;">{{ $label }}</td><td style="padding:2px 0;">{{ $value }}</td></tr>
            @endif
        @endforeach
    </table>

    <p style="margin:0;white-space:pre-line;">{{ $request->message }}</p>
@endsection
