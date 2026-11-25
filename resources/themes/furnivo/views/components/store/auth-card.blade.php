@props(['title', 'intro' => null])

{{-- Sign-in style pages: one centred card, in the design's type and spacing. --}}
<section style="width: 100%; padding: clamp(50px, 7vw, 96px) clamp(24px, 4.5vw, 80px); display: flex; justify-content: center;">
    <div style="width: min(460px, 100%);">
        <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 14px;">{{ settings()->trans('general.store_name') }}</div>
        <h1 style="margin: 0px 0px 12px; font-size: clamp(28px, 3.4vw, 40px); line-height: 1.08; font-weight: 800; letter-spacing: -0.03em;">{{ $title }}</h1>
        @if ($intro)
            <p style="margin: 0px 0px 28px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">{{ $intro }}</p>
        @endif

        @if (session('status'))
            <p role="status" style="margin: 0px 0px 20px; padding: 14px 16px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 14px;">{{ session('status') }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
