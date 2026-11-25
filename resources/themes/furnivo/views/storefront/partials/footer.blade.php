{{-- Furnivo footer — markup and styles from the design (site.zip). --}}
@php
    $footerCategories = \App\Models\Category::query()->active()->whereNull('parent_id')->orderBy('sort_order')->limit(6)->get();
    $page = fn (string $slug) => store_route('pages.show', ['slug' => $slug]);
    // The three link columns come from Content → Menus when those menus have items, so the
    // shop owner can change them; otherwise these are the design's own links.
    $menus = \App\Models\Menu::query()->whereIn('handle', ['footer-experience', 'footer-discover', 'footer-support'])
        ->with(['items' => fn ($q) => $q->orderBy('sort_order')])->get()->keyBy('handle');

    // A column's heading is its menu's name, so both are edited in one place.
    $heads = fn (string $handle, string $fallback) => $menus[$handle]->name ?? $fallback;

    $fromMenu = fn (string $handle) => $menus[$handle]?->items->map(fn ($item) => [$item->label, $item->href()])->all() ?: null;

    $columns = array_filter([
        $heads('footer-experience', 'Experience') => $fromMenu('footer-experience') ?: [['View Your Order', store_route('account.orders')], ['Request a Return', store_route('account.orders')], ['Offers', store_route('collections.show', ['slug' => 'sale'])]],
        $heads('footer-discover', 'Discover') => $fromMenu('footer-discover') ?: [['About us', $page('about')], ['Careers', $page('careers')], ['Warranty Policy', $page('warranty-policy')], ['Terms of Use', $page('terms')], ['Privacy Policy', $page('privacy')], ['Consumer Rights', $page('consumer-rights')]],
        $heads('footer-support', 'Support') => $fromMenu('footer-support') ?: [['Contact Us', $page('contact')], ['Delivery', $page('delivery')], ['Return & Refund', $page('delivery-returns')], ['FAQs', $page('faqs')]],
    ]);
    // Only the accounts the shop has actually filled in (Settings → Store details). An icon
    // linking to "#" is worse than no icon at all. WhatsApp comes from the phone number.
    $whatsapp = preg_replace('/[^0-9]/', '', (string) settings()->get('general.whatsapp'));
    $social = collect(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'x' => 'X', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'])
        ->map(fn ($label, $key) => ['label' => $label, 'url' => (string) settings()->get("general.{$key}_url")])
        ->when($whatsapp !== '', fn ($list) => $list->put('whatsapp', ['label' => 'WhatsApp', 'url' => 'https://wa.me/'.$whatsapp]))
        ->filter(fn ($item) => filled($item['url']));
    $link = 'cursor: pointer; font-size: 14px; line-height: 20px; color: rgb(156, 152, 146); transition: color 200ms;';
    $heading = 'font-size: 13px; letter-spacing: 0.16em; text-transform: uppercase; color: rgb(255, 255, 255); margin-bottom: 18px;';
@endphp
<footer class="fx-footer" style="position: relative; background: rgb(20, 20, 20); color: rgb(207, 204, 199); overflow: hidden;">
    <div class="fx-footer-inner fx-shell" style="position: relative; width: 100%; padding: clamp(56px, 7vw, 90px) clamp(24px, 4.5vw, 80px) 40px;">
        <div class="footer-grid fx-footer-columns" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: clamp(30px, 4vw, 56px);">
            <div>
                <div style="{{ $heading }}">{{ settings()->trans('theme.footer_categories_heading') ?: 'Categories' }}</div>
                <div style="display: flex; flex-direction: column; gap: 11px;">
                    @foreach ($footerCategories as $category)
                        <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" class="scpe" style="{{ $link }}"><span>{{ $category->name }}</span></a>
                    @endforeach
                </div>
            </div>
            @foreach ($columns as $title => $links)
                <div>
                    <div style="{{ $heading }}">{{ $title }}</div>
                    <div style="display: flex; flex-direction: column; gap: 11px;">
                        @foreach ($links as [$label, $url])
                            <a href="{{ $url }}" class="scpe" style="{{ $link }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @include('storefront.partials.newsletter')
    </div>
    <div class="footer-bottom fx-footer-bottom fx-shell" style="position: relative; border-top: 1px solid rgb(38, 38, 38); padding: 22px clamp(24px, 4.5vw, 80px); display: flex; align-items: center; justify-content: space-between; gap: 20px; font-size: 12px; letter-spacing: 0.06em; color: rgb(125, 122, 117); width: 100%;">
        <div>© {{ now()->year }} {{ $storeName }}. All rights reserved</div>
        <div style="display: flex; align-items: center; gap: 12px;">
            @foreach ($social as $key => $item)
                <a aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}" class="scpf" href="{{ $item['url'] }}" @if ($item['url'] !== '#') target="_blank" rel="noopener" @endif style="width: 36px; height: 36px; border: 1px solid rgb(58, 58, 58); display: flex; align-items: center; justify-content: center; color: rgb(207, 204, 199); transition: background 250ms, color 250ms;">
                    @switch($key)
                        @case('facebook')<svg fill="currentColor" height="16" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z"></path></svg>@break
                        @case('instagram')<svg fill="none" height="16" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" width="16" aria-hidden="true"><rect height="18" rx="5" width="18" x="3" y="3"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" fill="currentColor" r="1.1" stroke="none"></circle></svg>@break
                        @case('youtube')<svg fill="currentColor" height="16" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M22 8.2a2.6 2.6 0 0 0-1.8-1.8C18.6 6 12 6 12 6s-6.6 0-8.2.4A2.6 2.6 0 0 0 2 8.2 27 27 0 0 0 1.7 12 27 27 0 0 0 2 15.8a2.6 2.6 0 0 0 1.8 1.8C5.4 18 12 18 12 18s6.6 0 8.2-.4a2.6 2.6 0 0 0 1.8-1.8A27 27 0 0 0 22.3 12 27 27 0 0 0 22 8.2zM10 15V9l5 3z"></path></svg>@break
                        @case('x')<svg fill="currentColor" height="15" viewBox="0 0 24 24" width="15" aria-hidden="true"><path d="M18.2 2h3.3l-7.2 8.3L22.8 22h-6.6l-5.2-6.8L5 22H1.7l7.7-8.8L1.2 2h6.8l4.7 6.2L18.2 2Zm-1.2 18h1.8L7.1 3.9H5.2L17 20Z"/></svg>@break
                        @case('linkedin')<svg fill="currentColor" height="16" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M4.98 3.5A2.5 2.5 0 1 0 5 8.5a2.5 2.5 0 0 0-.02-5ZM3 9h4v12H3V9Zm7 0h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.4c0-1.29-.02-2.95-1.8-2.95-1.8 0-2.08 1.4-2.08 2.85V21h-4V9Z"/></svg>@break
                        @case('whatsapp')<svg fill="currentColor" height="16" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2Zm5.6 14.2c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6a11 11 0 0 1-4.2-3.9c-.3-.5-.7-1.2-.7-2s.4-1.2.6-1.4c.2-.2.4-.3.6-.3h.4c.2 0 .4 0 .5.4l.7 1.7c.1.2 0 .4-.1.5l-.3.4c-.1.1-.2.3-.1.5.2.4.7 1.1 1.4 1.7.8.7 1.5 1 1.8 1.1.2.1.4.1.5-.1l.6-.7c.2-.2.3-.2.5-.1l1.6.8c.2.1.4.2.4.3v.6Z"/></svg>@break
                        @case('tiktok')<svg fill="currentColor" height="15" viewBox="0 0 24 24" width="15" aria-hidden="true"><path d="M16 3c.3 2.3 1.7 3.9 4 4.2v3c-1.5 0-2.9-.4-4-1.1v6.4A5.5 5.5 0 1 1 10.5 10c.3 0 .7 0 1 .1v3.1a2.5 2.5 0 1 0 1.7 2.4V3H16z"></path></svg>@break
                    @endswitch
                </a>
            @endforeach
        </div>
    </div>
</footer>
