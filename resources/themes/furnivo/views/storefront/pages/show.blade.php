@extends('storefront.layouts.app')

{{-- Content pages (Delivery, Terms, FAQs, Contact …) — the design's header band, body and,
     where the page asks for it, the contact details and form. --}}
@section('furnivo', true)
@if ($page->slug === 'about')
    @section('overHero', true)
@endif
@section('title', $page->seo_title ?: $page->title)
@if (filled($page->seo_description))
    @section('meta_description', $page->seo_description)
@endif

@php
    $eyebrows = [
        'warranty-policy' => 'Assurance', 'terms' => 'Legal', 'privacy' => 'Legal',
        'consumer-rights' => 'Your rights', 'delivery' => 'Service', 'delivery-returns' => 'Service',
        'faqs' => 'Help centre', 'contact' => 'Contact', 'about' => 'Our story', 'careers' => 'Careers',
    ];
    $eyebrow = $page->eyebrow ?: ($eyebrows[$page->slug] ?? settings()->trans('general.store_name'));
    $headline = $page->heading ?: $page->title;
    // Centred column, as wide as the design's text (the FAQs list is a little wider).
    $column = 'max-width: '.($page->slug === 'faqs' ? '820px' : '760px').'; margin: 0px auto;';
    $isFaqs = $page->slug === 'faqs';
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    // The FAQs page: one heading per question, the answer under it.
    $faqs = $isFaqs ? collect(\App\Support\RichText::sections($page->html())['sections'])
        ->map(fn ($s) => ['q' => $s['h'], 'a' => $s['html']])->filter(fn ($faq) => $faq['q'] !== '')->values() : collect();
@endphp

@push('meta')
    <script type="application/ld+json">{!! \App\Services\Seo\StructuredData::encode(app(\App\Services\Seo\StructuredData::class)->breadcrumbs([['label' => $page->title, 'url' => store_route('pages.show', ['slug' => $page->slug])]])) !!}</script>
@endpush

@section('content')
@if ($page->slug === 'about')
    @include('storefront.pages.about', ['page' => $page])
@elseif ($page->slug === 'careers')
    @include('storefront.pages.careers', ['page' => $page, 'eyebrow' => $eyebrow, 'headline' => $headline])
@else
    <section style="width: 100%; background: rgb(245, 244, 240); padding: clamp(20px, 2.5vw, 32px) clamp(24px, 4.5vw, 80px) clamp(36px, 4.5vw, 56px);">
        <div style="{{ $page->slug === 'contact' ? '' : $column }}">
        <nav aria-label="{{ __('store.theme.breadcrumb') }}" style="font-size: 13px; color: rgb(102, 102, 102); margin-bottom: 24px;">
            <a href="{{ store_route('home') }}" style="cursor: pointer; color: inherit;">{{ __('store.theme.home') }}</a> &nbsp;/&nbsp;
            <span style="color: rgb(32, 32, 32);" aria-current="page">{{ $page->title }}</span>
        </nav>
        <div style="font-size: 12px; letter-spacing: 0.28em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 16px;"><span>{{ $eyebrow }}</span></div>
        <h1 style="margin: 0px{{ filled($page->intro) ? ' 0px 18px' : '' }}; font-size: clamp(34px, 4.4vw, 56px); line-height: 1.04; font-weight: 800; letter-spacing: -0.03em;">{{ $headline }}</h1>
        @if (filled($page->intro))
            <p style="margin: 0px; font-size: 18px; line-height: 30px; color: rgb(102, 102, 102);">{{ $page->intro }}</p>
        @endif
        </div>
    </section>

    <section style="width: 100%; padding: clamp(36px, 5vw, 64px) clamp(24px, 4.5vw, 80px) clamp(60px, 7vw, 100px);">
        <div style="{{ $page->slug === 'contact' ? '' : $column }}">
            @if ($isFaqs && $faqs->isNotEmpty())
                <div x-data="{ open: null, term: '' }">
                    <div style="display: flex; align-items: center; gap: 10px; border: 1px solid rgb(217, 213, 207); background: rgb(255, 255, 255); padding: 0px 14px; margin-bottom: 26px;">
                        <span aria-hidden="true" style="color: rgb(168, 164, 158); font-size: 14px;">⌕</span>
                        <input type="search" x-model="term" placeholder="{{ __('store.faqs.search') }}" aria-label="{{ __('store.faqs.search') }}" style="flex: 1 1 0%; height: 50px; border: none; background: transparent; font-size: 15px; color: rgb(32, 32, 32); outline: none;">
                    </div>

                    @foreach ($faqs as $i => $faq)
                        <div x-show="! term || @js(mb_strtolower($faq['q'].' '.\App\Support\RichText::text($faq['a']))).includes(term.toLowerCase())">
                            <div style="border-bottom: 1px solid rgb(236, 236, 236);">
                                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}"
                                        style="width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 0px; border: none; background: transparent; text-align: start; font-size: 16px; color: rgb(32, 32, 32);">
                                    <span>{{ $faq['q'] }}</span>
                                    <span aria-hidden="true" style="font-size: 18px; color: rgb(139, 135, 127); transition: transform 250ms;" :style="{ transform: open === {{ $i }} ? 'rotate(45deg)' : 'rotate(0deg)' }">+</span>
                                </button>
                                <div x-show="open === {{ $i }}" x-cloak>
                                    <div class="fv-rich" style="padding: 0px 0px 22px; font-size: 15px; line-height: 25px; color: rgb(102, 102, 102);">{!! $faq['a'] !!}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <p x-show="term && ! {{ $faqs->count() }}" x-cloak style="margin: 20px 0px 0px; font-size: 15px; color: rgb(102, 102, 102);">{{ __('store.faqs.none') }}</p>
                </div>
            @else
                <div class="fv-prose">{!! $page->html() !!}</div>
            @endif

            @if (filled($page->footnote))
                <div style="margin-top: 48px; padding-top: 28px; border-top: 1px solid rgb(236, 236, 236); font-size: 13px; color: rgb(139, 135, 127);">{{ $page->footnote }}</div>
            @endif

            @if ($page->slug === 'contact')
                @include('storefront.pages.contact', ['page' => $page])
            @endif

            @if ($page->show_contact_form && $page->slug !== 'contact')
                <div style="margin-top: clamp(40px, 5vw, 64px); padding-top: clamp(32px, 4vw, 48px); border-top: 1px solid rgb(236, 236, 236);">
                    <h2 style="margin: 0px 0px 8px; font-size: clamp(20px, 2.4vw, 28px); font-weight: 700; letter-spacing: -0.02em;">{{ __('store.contact.title') }}</h2>
                    <p style="margin: 0px 0px 26px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">{{ __('store.contact.intro') }}</p>

                    @if (session('contact_sent'))
                        <p role="status" style="margin: 0px; padding: 16px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 15px;">{{ __('store.contact.sent') }}</p>
                    @else
                        <form method="POST" action="{{ store_route('pages.contact', ['slug' => $page->slug]) }}" class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                            @csrf
                            {{-- Bots fill this in; people never see it. --}}
                            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);" aria-hidden="true">

                            <label style="{{ $label }}"><span>{{ __('store.checkout.full_name') }}<x-store.required /></span>
                                <input name="name" value="{{ old('name', auth('web')->user()?->name) }}" required maxlength="120" autocomplete="name" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }}"><span>{{ __('store.auth.email') }}<x-store.required /></span>
                                <input type="email" name="email" value="{{ old('email', auth('web')->user()?->email) }}" required maxlength="255" autocomplete="email" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }} grid-column: 1 / -1;">{{ __('store.auth.phone') }}
                                <input name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" dir="ltr" style="{{ $input }}">
                            </label>
                            <label style="{{ $label }} grid-column: 1 / -1;"><span>{{ __('store.contact.message') }}<x-store.required /></span>
                                <textarea name="message" rows="5" required minlength="10" maxlength="3000" style="{{ $input }} resize: vertical;">{{ old('message') }}</textarea>
                            </label>

                            @foreach (['name', 'email', 'phone', 'message', 'g-recaptcha-response'] as $field)
                                @error($field)<p role="alert" style="grid-column: 1 / -1; margin: 0px; font-size: 13px; color: rgb(199, 64, 52);">{{ $message }}</p>@enderror
                            @endforeach

                            <div style="grid-column: 1 / -1;">
                                <button style="height: 52px; padding: 0px 34px; border: 1px solid rgb(29, 29, 29); background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase; cursor: pointer;">{{ __('store.contact.send') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif
@endsection
