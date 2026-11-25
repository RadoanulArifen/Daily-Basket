{{-- Brand story: an editorial band. Text comes from Online Store → Customize; the picture
     from the first hero slide, so it never renders empty. --}}
@php
    $storyTitle = settings()->trans('theme.story_title') ?: __('store.theme.story_title');
    $storyText = settings()->trans('theme.story_text') ?: __('store.theme.story_text');
    $storyImage = ($slides->first()?->image) ?? null;
@endphp
<section style="border-top: 1px solid rgb(236, 236, 236); padding: clamp(56px, 7vw, 96px) 0px;" aria-labelledby="fv-story">
    <div style="{{ $pad }}; display: grid; gap: clamp(28px, 4vw, 64px); grid-template-columns: 1fr; align-items: center;"
         class="fv-story-grid">
        <div>
            <p style="font-size: 12px; letter-spacing: 0.3em; text-transform: uppercase; color: rgb(139, 135, 127);">{{ __('store.theme.story_eyebrow') }}</p>
            <h2 id="fv-story" style="margin-top: 14px; font-size: clamp(26px, 3vw, 40px); line-height: 1.1; letter-spacing: -0.03em; color: rgb(29, 29, 29);">{{ $storyTitle }}</h2>
            <p style="margin-top: 16px; font-size: 16px; line-height: 1.7; color: rgb(102, 102, 102); max-width: 52ch;">{{ $storyText }}</p>
            <a href="{{ store_route('shop') }}" style="display: inline-flex; align-items: center; height: 52px; padding: 0px 30px; margin-top: 26px; border: 1px solid rgb(29, 29, 29); color: rgb(29, 29, 29); text-decoration: none; font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase;">{{ __('store.theme.shop_all') }}</a>
        </div>
        @if ($storyImage)
            <img src="{{ $storyImage->url('large') }}" alt="" loading="lazy"
                 style="width: 100%; height: clamp(260px, 38vw, 520px); object-fit: cover;">
        @endif
    </div>
</section>
