{{-- Contact page — the design's two columns: how to reach the store, and the form. --}}
@php
    $settings = settings();
    $label = 'display: flex; flex-direction: column; gap: 7px; font-size: 12px; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(102, 102, 102);';
    $input = 'border: 1px solid rgb(217, 213, 207); padding: 13px 14px; font-size: 15px; color: rgb(32, 32, 32); background: rgb(255, 255, 255);';
    $eyebrow = 'font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(102, 102, 102); margin-bottom: 8px;';
    $value = 'font-size: 18px; line-height: 28px; color: rgb(32, 32, 32);';
    $ways = collect([
        ['label' => __('store.contact.address'), 'value' => $settings->trans('general.address'), 'url' => null],
        ['label' => __('store.contact.phone'), 'value' => $settings->get('general.store_phone'), 'url' => 'tel:'.preg_replace('/\s+/', '', (string) $settings->get('general.store_phone'))],
        ['label' => __('store.auth.email'), 'value' => $settings->get('general.store_email'), 'url' => 'mailto:'.$settings->get('general.store_email')],
        ['label' => __('store.contact.whatsapp'), 'value' => $settings->get('general.whatsapp'), 'url' => 'https://wa.me/'.preg_replace('/\D/', '', (string) $settings->get('general.whatsapp'))],
        ['label' => __('store.contact.hours'), 'value' => $settings->trans('general.opening_hours'), 'url' => null],
    ])->filter(fn ($way) => filled($way['value']));
    $socials = collect([
        'Instagram' => $settings->get('general.instagram_url'),
        'Facebook' => $settings->get('general.facebook_url'),
        'TikTok' => $settings->get('general.tiktok_url'),
    ])->filter();
@endphp

<div class="two-col fx-split-section" style="display: grid; grid-template-columns: 1fr 1.1fr; gap: clamp(40px, 6vw, 80px); align-items: start;">
    <div style="min-width: 0px;">
        <div style="display: flex; flex-direction: column; gap: 28px; margin-bottom: 36px;">
            @foreach ($ways as $way)
                <div>
                    <div style="{{ $eyebrow }}">{{ $way['label'] }}</div>
                    @if ($way['url'])
                        <a href="{{ $way['url'] }}" style="{{ $value }}" dir="auto">{{ $way['value'] }}</a>
                    @else
                        <div style="{{ $value }}" dir="auto">{!! nl2br(e($way['value'])) !!}</div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($socials->isNotEmpty())
            <div style="display: flex; gap: 22px; font-size: 13px; letter-spacing: 0.06em; color: rgb(102, 102, 102);">
                @foreach ($socials as $name => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" style="cursor: pointer; color: inherit;">{{ $name }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <div style="min-width: 0px; border: 1px solid rgb(236, 236, 236); padding: clamp(24px, 3vw, 40px);">
        <h2 style="margin: 0px 0px 8px; font-size: 24px; font-weight: 700; letter-spacing: -0.01em;">{{ __('store.contact.title') }}</h2>
        <p style="margin: 0px 0px 26px; font-size: 15px; line-height: 24px; color: rgb(102, 102, 102);">{{ __('store.contact.intro') }}</p>

        @if (session('contact_sent'))
            <p role="status" style="margin: 0px; padding: 16px 18px; background: rgb(250, 249, 247); border: 1px solid rgb(236, 236, 236); font-size: 15px;">{{ __('store.contact.sent') }}</p>
        @else
            <form method="POST" action="{{ store_route('pages.contact', ['slug' => $page->slug]) }}" class="fx-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px;">
                @csrf
                {{-- Bots fill this in; people never see it. --}}
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">

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
</div>
