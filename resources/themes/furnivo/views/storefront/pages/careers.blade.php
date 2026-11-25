{{-- Careers — the design's layout: header band, then one row per role linking to the contact
     page. Roles: Content → Pages → Careers, one Heading per role with a line under it
     (team · place · hours). Any text before the first role is shown above the list. --}}
@php
    $parts = \App\Support\RichText::sections($page->html());
    $contact = store_route('pages.show', ['slug' => 'contact']);
@endphp
<section style="width: 100%; background: rgb(245, 244, 240); padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(48px, 5vw, 64px);">
    <div style="max-width: 900px; margin: 0px auto;">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ $page->title }}</span>
        </nav>
        <div style="max-width: 760px;">
            <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 16px;">{{ $eyebrow }}</div>
            <h1 style="margin: 0px{{ filled($page->intro) ? ' 0px 18px' : '' }}; font-size: clamp(34px, 4.4vw, 56px); line-height: 1.04; font-weight: 800; letter-spacing: -0.03em;">{{ $headline }}</h1>
            @if (filled($page->intro))
                <p style="margin: 0px; font-size: 18px; line-height: 30px; color: rgb(102, 102, 102);">{{ $page->intro }}</p>
            @endif
        </div>
    </div>
</section>
<section style="width: 100%; padding: 0px clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 110px);">
    <div style="max-width: 900px; margin: 0px auto;">
        @if ($parts['lead'] !== '')
            <div class="fv-prose" style="padding-top: clamp(36px, 5vw, 56px); margin-bottom: 28px;">{!! $parts['lead'] !!}</div>
        @endif
        <div style="border-top: 1px solid rgb(236, 236, 236);">
            @foreach ($parts['sections'] as $role)
                <a href="{{ $contact }}" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding: 26px 0px; border-bottom: 1px solid rgb(236, 236, 236); color: inherit;">
                    <div>
                        <div style="font-size: 19px; font-weight: 600; color: rgb(32, 32, 32); letter-spacing: -0.01em; margin-bottom: 6px;">{{ $role['h'] }}</div>
                        @if (($meta = \App\Support\RichText::text($role['html'])) !== '')
                            <div style="font-size: 13px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(139, 135, 127);">{{ $meta }}</div>
                        @endif
                    </div>
                    <span style="font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29); white-space: nowrap;">{{ __('store.theme.apply') }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
        @if (filled($page->footnote))
            <div style="margin-top: 48px; padding-top: 28px; border-top: 1px solid rgb(236, 236, 236); font-size: 13px; color: rgb(139, 135, 127);">{{ $page->footnote }}</div>
        @endif
    </div>
</section>
