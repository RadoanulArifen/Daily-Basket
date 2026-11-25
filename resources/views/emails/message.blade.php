@extends('emails.orders.layout')

{{-- A plain customer email: greeting, the editable intro, optional details and a button.
     Used by the welcome, enquiry and contact-received emails. --}}
@section('body')
    @if (! empty($greeting))
        <p style="margin:0 0 14px;">{{ $greeting }}</p>
    @endif

    <p style="margin:0 0 18px;">{!! nl2br(e($intro)) !!}</p>

    @if (! empty($details))
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;border-collapse:collapse;">
            @foreach ($details as $label => $value)
                @continue (blank($value))
                <tr>
                    <td style="padding:6px 0;font-size:13px;color:#8b877f;width:40%;vertical-align:top;">{{ $label }}</td>
                    <td style="padding:6px 0;font-size:14px;vertical-align:top;">{!! nl2br(e($value)) !!}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (! empty($actionUrl) && ! empty($actionText))
        <p style="margin:22px 0 0;">
            <a href="{{ $actionUrl }}" style="display:inline-block;background:#1d1d1d;color:#ffffff;text-decoration:none;padding:12px 24px;font-size:14px;">{{ $actionText }}</a>
        </p>
    @endif

    @if (! empty($footnote))
        <p style="margin:22px 0 0;font-size:13px;color:#8b877f;">{{ $footnote }}</p>
    @endif
@endsection
