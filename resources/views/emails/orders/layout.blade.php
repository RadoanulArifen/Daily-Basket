{{-- Email-client-safe layout: tables, inline styles, no web fonts, no background images. --}}
@php
    $rtl = app()->getLocale() === 'ar';
    $store = settings()->trans('general.store_name') ?? config('app.name');
    $logoFile = settings()->get('general.logo');
    $logo = $logoFile ? \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($logoFile) : null;
    $email = settings()->get('general.store_email');
    $phone = settings()->get('general.store_phone');
    $address = settings()->trans('general.address');
    $align = $rtl ? 'right' : 'left';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $store }}</title>
</head>
<body style="margin:0;padding:0;background:#f1efec;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#202020;-webkit-font-smoothing:antialiased;">
    {{-- Preview text: the first line inboxes show beside the subject. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preview', $store)</div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1efec;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e8e4dd;text-align:{{ $align }};">
                    {{-- A dark band and the shop's mark, the way the storefront opens. --}}
                    <tr><td style="height:4px;background:#1d1d1d;font-size:0;line-height:0;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:28px 36px 22px;border-bottom:1px solid #f0ede8;">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $store }}" height="28" style="height:28px;width:auto;display:block;border:0;">
                            @else
                                <span style="font-size:19px;font-weight:bold;letter-spacing:-0.01em;color:#1d1d1d;">{{ $store }}</span>
                            @endif
                        </td>
                    </tr>

                    <tr><td style="padding:30px 36px 34px;font-size:15px;line-height:1.65;color:#3d3d3d;">@yield('body')</td></tr>
                </table>

                {{-- Who it is from, so the message stands on its own. --}}
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;">
                    <tr>
                        <td style="padding:20px 36px 0;text-align:center;font-size:12px;line-height:1.7;color:#8b877f;">
                            <strong style="color:#6f6b64;">{{ $store }}</strong><br>
                            @if ($address){{ str_replace("\n", ' · ', trim($address)) }}<br>@endif
                            @if ($email)<a href="mailto:{{ $email }}" style="color:#8b877f;text-decoration:none;">{{ $email }}</a>@endif
                            @if ($email && $phone) · @endif
                            @if ($phone)<span dir="ltr">{{ $phone }}</span>@endif
                        </td>
                    </tr>
                    @hasSection('footer_note')
                        <tr><td style="padding:10px 36px 0;text-align:center;font-size:12px;color:#a8a49e;">@yield('footer_note')</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
