@extends('emails.orders.layout')

@section('body')
    <p style="margin:0 0 16px;white-space:pre-line;">{{ $intro }}</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e6e1d8;">
        @foreach ($lines as $line)
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #e6e1d8;width:64px;">@if ($line['image'])<img src="{{ $line['image'] }}" alt="" width="56" height="70" style="display:block;border-radius:4px;object-fit:cover;">@endif</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e6e1d8;">{{ $line['name'] }}@if ($line['variant'])<br><span style="color:#7d776c;font-size:13px;">{{ $line['variant'] }}</span>@endif<br><span style="color:#7d776c;font-size:13px;">× {{ $line['quantity'] }}</span></td>
                <td style="padding:10px 0;border-bottom:1px solid #e6e1d8;white-space:nowrap;text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }};">{{ $line['price'] }}</td>
            </tr>
        @endforeach
    </table>
    <p style="margin:28px 0 0;"><a href="{{ $restoreUrl }}" style="display:inline-block;background:#22262a;color:#fcfbf9;text-decoration:none;padding:12px 22px;border-radius:6px;">{{ __('store.reminder.button') }}</a></p>
    <p style="margin:28px 0 0;font-size:12px;color:#7d776c;">{{ __('store.reminder.why') }} <a href="{{ $unsubscribeUrl }}" style="color:#7d776c;">{{ __('store.reminder.unsubscribe') }}</a></p>
@endsection
