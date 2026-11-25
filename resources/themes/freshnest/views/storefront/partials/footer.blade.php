{{--
    FreshNest footer — markup and styles from the design: the delivery strip, the brand
    block, four link columns (folding on phones), the copyright bar with payment marks,
    the floating Support / back-to-top buttons and the phone bottom navigation.

    Where the words come from:
      strip heading      Settings → Store details → tagline
      strip sub-line     Settings → Shipping (free-delivery threshold)
      brand paragraph    Settings → Store details → meta description
      columns 1–3        Content → Menus → footer-shop, footer-company, footer-help
                         (the menu's name is the column heading)
      Contact column     Settings → Store details (phone, email, address, opening hours)
      social icons       Settings → Store details → social links (only those filled in)
      payment marks      shown while card payments (Stripe) are switched on
--}}
@php
    $settingsSvc = settings();
    $freeAt = app(\App\Themes\Freshnest\Delivery::class)->freeThreshold();
    $uploads = \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'));
    $logo = ($f = $settingsSvc->get('general.logo_white') ?: $settingsSvc->get('general.logo')) && ! in_array($f, [\Database\Seeders\FreshnestSeeder::LOGO_PATH, \Database\Seeders\FreshnestSeeder::LOGO_WHITE_PATH], true) ? $uploads->url($f) : null; // the bundled logo = the design's own drawn mark

    $menus = app(\App\Services\Navigation\MenuRepository::class);
    $chev = 'M9 6l6 6-6 6';
    $columns = collect(['footer-shop', 'footer-company', 'footer-help'])
        ->map(fn ($handle) => $menus->get($handle))->filter(fn ($menu) => $menu && $menu->tree->isNotEmpty())
        ->map(fn ($menu) => ['title' => __($menu->name), 'links' => $menu->tree->map(fn ($item) => [
            't' => $item->label, 'href' => $item->href(), 'icon' => $chev, 'box' => '14px', 'sw' => '2.6', 'bg' => 'transparent', 'mt' => '3px',
        ])->all()])->values();

    $firstLine = fn (?string $text) => $text ? trim(strtok(str_replace("\r", '', $text), "\n")) : null;
    $contactPage = store_route('pages.show', ['slug' => 'contact']);
    $phone = $settingsSvc->get('general.store_phone');
    $email = $settingsSvc->get('general.store_email');
    $contact = collect([
        $phone ? ['t' => $phone, 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $phone), 'icon' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z'] : null,
        $email ? ['t' => $email, 'href' => 'mailto:'.$email, 'icon' => 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 2 8 6 8-6'] : null,
        ($a = $firstLine($settingsSvc->trans('general.address'))) ? ['t' => $a, 'href' => $contactPage, 'icon' => 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Zm0-14a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z'] : null,
        ($h = $firstLine($settingsSvc->trans('general.opening_hours'))) ? ['t' => $h, 'href' => $contactPage, 'icon' => 'M12 3.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17ZM12 7.5V12l3 2'] : null,
    ])->filter()->map(fn ($l) => $l + ['box' => '30px', 'sw' => '1.9', 'bg' => 'rgba(138,79,19,.12)', 'mt' => '-4px'])->values()->all();
    if ($contact) {
        $columns->push(['title' => __('Contact'), 'links' => $contact]);
    }

    $social = collect(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'x' => 'X', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn'])
        ->map(fn ($label, $key) => ['label' => $label, 'url' => (string) $settingsSvc->get("general.{$key}_url")])
        ->filter(fn ($item) => filled($item['url']));
    $cards = $settingsSvc->bool('payments.stripe_enabled');
    $page = fn (string $slug) => store_route('pages.show', ['slug' => $slug]);
    $customer = auth('web')->user();
@endphp
  <footer id="site-footer" style="position:relative; overflow:hidden; background:var(--fn-footer); color:#FFFFFF; padding-top:clamp(44px,5.5vw,76px); margin-top:clamp(20px,3vw,40px);">
    <svg width="320" height="320" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="position:absolute; left:-90px; top:40px; opacity:.05; transform:rotate(-25deg);"><path d="M3 21C3 11 11 3 21 3c0 10-8 18-18 18Z" fill="#FFFFFF"></path></svg>

    <div class="wrap" style="position:relative; padding-bottom:clamp(36px,4.5vw,56px);">
      <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:clamp(20px,2.6vw,30px); border-radius:24px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); margin-bottom:clamp(36px,4.5vw,56px);">
        <div style="display:flex; align-items:center; gap:16px;">
          <span style="display:inline-flex; width:52px; height:52px; flex:0 0 auto; border-radius:16px; background:var(--fn-accent); align-items:center; justify-content:center; box-shadow:0 12px 26px rgba(138,79,19,.35);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 7h10.5v9H2.5"></path><path d="M13 10h4.2l3.3 3.3V16H13"></path><circle cx="6.5" cy="17" r="1.8"></circle><circle cx="17" cy="17" r="1.8"></circle></svg></span>
          <span style="line-height:1.3;"><strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:clamp(18px,2vw,22px);">{{ $settingsSvc->trans('general.tagline') }}</strong>@if ($freeAt)<span style="font-size:14px; color:#B4B9BF;">{{ __('Free delivery on every order over') }} {{ price_whole($freeAt) }}.</span>@endif</span>
        </div>
        <a class="fh-74ffb8" href="{{ store_route('shop') }}" style="display:inline-flex; align-items:center; gap:12px; background:#FFFFFF; color:var(--fn-primary-dark); font-weight:700; font-size:15px; padding:6px 6px 6px 20px; border-radius:999px;"><span style="white-space:nowrap;">{{ __('Start Shopping') }}</span><span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:var(--fn-primary-dark); align-items:center; justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg></span></a>
      </div>

      <div class="foot-grid">
        <div>
          <a href="{{ store_route('home') }}" aria-label="{{ $storeName }}" style="display:flex; align-items:center; gap:10px;">
            @if ($logo)
              <img src="{{ $logo }}" alt="{{ $storeName }}" style="height:40px; width:auto; max-width:220px; object-fit:contain;">
            @else
            <img src="{{ asset('themes/freshnest/favicon.png') }}?v=2" alt="" width="40" height="40">
            <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; ; color:var(--fn-primary)">{{ $storeName }}</span>
            @endif
          </a>
          @if (filled($about = $settingsSvc->trans('general.meta_description')))
          <p style="margin:18px 0 0; font-size:14.5px; line-height:1.7; color:#B4B9BF; max-width:340px;">{{ $about }}</p>
          @endif
          @if ($social->isNotEmpty())
          <div style="display:flex; gap:10px; margin-top:20px;">
            @foreach ($social as $key => $item)
            <a class="fh-425f45" href="{{ $item['url'] }}" target="_blank" rel="noopener" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}" style="display:inline-flex; width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); align-items:center; justify-content:center; color:#FFFFFF;">@switch($key)
                @case('instagram')<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.2"></circle><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"></circle></svg>@break
                @case('facebook')<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M13.5 21v-7.6h2.6l.4-3h-3V8.5c0-.9.3-1.5 1.5-1.5h1.6V4.3c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21h3.1Z"></path></svg>@break
                @case('x')<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M17.6 3h3.1l-6.8 7.8L22 21h-6.3l-4.9-6.4L5.2 21H2.1l7.3-8.3L1.8 3h6.4l4.4 5.8L17.6 3Zm-1.1 16.2h1.7L7.6 4.7H5.8l10.7 14.5Z"></path></svg>@break
                @case('youtube')<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12c0 1.3.1 2.6.4 3.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1c.3-1.2.4-2.5.4-3.8s-.1-2.6-.4-3.8ZM10 15.2V8.8l5.2 3.2-5.2 3.2Z"></path></svg>@break
                @case('tiktok')<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M16.6 3c.4 2.2 1.8 3.7 4 3.9v3.1c-1.5 0-2.8-.4-4-1.2v6.3c0 3.3-2.6 5.9-5.9 5.9S4.8 18.4 4.8 15.1s2.6-5.9 5.9-5.9c.3 0 .6 0 .9.1v3.2a2.8 2.8 0 1 0 1.9 2.6V3h3.1Z"></path></svg>@break
                @default<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M4.5 9h3v11h-3V9Zm1.5-5a1.8 1.8 0 1 1 0 3.6A1.8 1.8 0 0 1 6 4Zm4 5h2.9v1.5h.1c.4-.8 1.4-1.7 3-1.7 3.2 0 3.8 2.1 3.8 4.8V20h-3v-5.6c0-1.3 0-3-1.9-3s-2.1 1.4-2.1 2.9V20h-2.8V9Z"></path></svg>
            @endswitch</a>
            @endforeach
          </div>
          @endif
        </div>
        @foreach ($columns as $col)
          <div class="fn-fcol" x-data="{ open: false }" :class="open ? 'is-open' : ''">
            <button type="button" class="fn-fhead" @click="open = ! open" :aria-expanded="open" style="width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; background:transparent; border:0; padding:0; text-align:left;">
              <span style="display:inline-flex; align-items:center; gap:8px; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:16px; color:#FFFFFF;"><span style="display:inline-block; width:14px; height:3px; border-radius:2px; background:var(--fn-accent);"></span>{{ $col['title'] }}</span>
              <span class="fn-fchev" style="font-size:16px; color:#B4B9BF;" x-text="open ? '–' : '+'">+</span>
            </button>
              <div class="fn-flinks" style="display:flex; flex-direction:column; gap:12px; margin-top:18px;">
                @foreach ($col['links'] as $l)
                  <a class="fh-973ee6" href="{{ $l['href'] }}" style="display:flex; align-items:flex-start; gap:10px; font-size:14px; line-height:1.5; color:#B4B9BF;">
                    <span style="display:inline-flex; width:{{ $l['box'] }}; height:{{ $l['box'] }}; flex:0 0 auto; border-radius:50%; background:{{ $l['bg'] }}; align-items:center; justify-content:center; margin-top:{{ $l['mt'] }};"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="{{ $l['sw'] }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $l['icon'] }}"></path></svg></span>
                    <span style="min-width:0;">{{ $l['t'] }}</span>
                  </a>
                @endforeach
              </div>
          </div>
        @endforeach
      </div>

    </div>

    <div style="position:relative; background:var(--fn-footer-deep); border-top:1px solid rgba(255,255,255,.08);">
      <div class="wrap" style="padding-top:18px; padding-bottom:18px; display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; text-align:center; gap:8px 18px; line-height:1.5;"><span style="font-size:13.5px; color:#B4B9BF;">© {{ now()->year }} {{ $storeName }}{{ __('. All rights reserved.') }}</span><a class="fh-973ee6" href="{{ $page('privacy-policy') }}" style="font-size:13.5px; color:#B4B9BF;">{{ __('Privacy Policy') }}</a><a class="fh-973ee6" href="{{ $page('terms-conditions') }}" style="font-size:13.5px; color:#B4B9BF;">{{ __('Terms & Conditions') }}</a></div>
        <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
        @include('storefront.partials.switchers', ['on' => 'dark'])
        @if ($cards)
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <span title="{{ __('Visa') }}" aria-label="{{ __('Visa') }}" style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:36px; border-radius:8px; background:#FFFFFF; border:1px solid var(--fn-line); box-shadow:0 1px 3px rgba(25,75,48,.06);"><svg width="40" height="14" viewBox="0 0 40 14" aria-hidden="true"><text x="20" y="12" text-anchor="middle" font-family="Arial Black, Arial, sans-serif" font-weight="900" font-style="italic" font-size="14" fill="#1A1F71" letter-spacing=".5">VISA</text></svg></span>
        <span title="{{ __('Mastercard') }}" aria-label="{{ __('Mastercard') }}" style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:36px; border-radius:8px; background:#FFFFFF; border:1px solid var(--fn-line); box-shadow:0 1px 3px rgba(25,75,48,.06);"><svg width="34" height="22" viewBox="0 0 34 22" aria-hidden="true"><circle cx="12" cy="11" r="10" fill="#EB001B"></circle><circle cx="22" cy="11" r="10" fill="#F79E1B"></circle><path d="M17 2.34a10 10 0 0 1 0 17.32 10 10 0 0 1 0-17.32Z" fill="#FF5F00"></path></svg></span>
        <span title="{{ __('American Express') }}" aria-label="{{ __('American Express') }}" style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:36px; border-radius:8px; background:#2E77BC; border:1px solid var(--fn-line); box-shadow:0 1px 3px rgba(25,75,48,.06);"><svg width="44" height="14" viewBox="0 0 44 14" aria-hidden="true"><text x="22" y="11.5" text-anchor="middle" font-family="Arial, sans-serif" font-weight="900" font-size="11.5" fill="#FFFFFF" letter-spacing=".8">AMEX</text></svg></span>
        <span title="{{ __('Apple Pay') }}" aria-label="{{ __('Apple Pay') }}" style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:36px; border-radius:8px; background:#FFFFFF; border:1px solid var(--fn-line); box-shadow:0 1px 3px rgba(25,75,48,.06);"><svg width="44" height="18" viewBox="0 0 44 18" aria-hidden="true"><g transform="translate(2 1) scale(.62)" fill="#000000"><path d="M16.37 1.43c0 1.14-.42 2.2-1.13 3-.85.97-2.08 1.6-3.13 1.52-.13-1.1.4-2.26 1.1-3.03.8-.88 2.15-1.53 3.16-1.49ZM20.5 17.3c-.55 1.27-.82 1.84-1.53 2.96-.99 1.56-2.39 3.5-4.12 3.52-1.54.01-1.93-1-4.02-.99-2.09.01-2.52 1.01-4.06.99-1.73-.02-3.05-1.77-4.04-3.33C-.03 16.1-.32 11.02 1.43 8.33c1.24-1.9 3.2-3.02 5.04-3.02 1.88 0 3.06 1.03 4.61 1.03 1.51 0 2.43-1.03 4.6-1.03 1.64 0 3.38.9 4.62 2.44-4.06 2.22-3.4 8.02.2 9.55Z"></path></g><text x="18" y="14" font-family="-apple-system, Helvetica, Arial, sans-serif" font-weight="600" font-size="13.5" fill="#000000">Pay</text></svg></span>
        <span title="{{ __('Google Pay') }}" aria-label="{{ __('Google Pay') }}" style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:36px; border-radius:8px; background:#FFFFFF; border:1px solid var(--fn-line); box-shadow:0 1px 3px rgba(25,75,48,.06);"><svg width="44" height="18" viewBox="0 0 44 18" aria-hidden="true"><text x="1" y="14.5" font-family="Arial, sans-serif" font-weight="700" font-size="15"><tspan fill="#4285F4">G</tspan></text><text x="15" y="14" font-family="Arial, sans-serif" font-weight="500" font-size="13.5" fill="#5F6368">Pay</text></svg></span>
      </div>
        @endif
        </div>
        <a class="fh-425f45" href="{{ route('admin.dashboard', absolute: false) }}"
           aria-label="{{ __('store.common.admin_panel') }}" title="{{ __('store.common.admin_panel') }}"
           style="display:inline-flex; width:40px; height:40px; flex:0 0 auto; margin-inline-start:auto; align-items:center; justify-content:center; border-radius:50%; border:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.08); color:#FFFFFF;">
          <x-icon name="layout-dashboard" style="width:16px; height:16px;" />
        </a>
      </div>
    </div>
    <div class="mob-only" style="height:78px;"></div>
  </footer>
  <div class="fab-stack" x-data="{ showTop: false }" @scroll.window.throttle.100ms="showTop = window.scrollY > 500" style="position:fixed; right:clamp(14px,2vw,28px); z-index:55; display:flex; flex-direction:column; align-items:flex-end; gap:12px;">
    <a class="fh-ed87d8" href="{{ $contactPage }}" aria-label="{{ __('Customer support') }}" style="display:flex; align-items:center; gap:10px; height:52px; padding:0 20px 0 8px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14.5px; box-shadow:0 14px 30px rgba(35,99,63,.35);">
      <span style="display:inline-flex; width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.18); align-items:center; justify-content:center;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"></path><rect x="3" y="13" width="4" height="6" rx="1.5"></rect><rect x="17" y="13" width="4" height="6" rx="1.5"></rect><path d="M19 19c0 1.5-1.8 2.5-5 2.5"></path></svg></span>
      <span class="fab-label">{{ __('Support') }}</span>
    </a>
    <template x-if="showTop">
      <button class="fh-f80239" type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" aria-label="{{ __('Scroll to top') }}" style="width:52px; height:52px; border-radius:50%; border:0; background:var(--fn-accent); color:#FFFFFF; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 14px 30px rgba(138,79,19,.38);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path></svg></button>
    </template>
  </div>

  @php
    $homeActive = request()->routeIs('home', '*.home');
    $bottomNav = [
        ['label' => __('Home'), 'href' => store_route('home'), 'icon' => 'M4 11 12 4l8 7v8.5a1 1 0 0 1-1 1h-4.5V15h-5v5.5H5a1 1 0 0 1-1-1Z', 'active' => $homeActive],
        ['label' => __('Categories'), 'href' => store_route('shop'), 'icon' => 'M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z', 'active' => request()->routeIs('shop', '*.shop', 'collections.*', '*.collections.*')],
        ['label' => __('Search'), 'href' => store_route('search'), 'icon' => 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14ZM20 20l-3.5-3.5', 'active' => request()->routeIs('search', '*.search')],
        ['label' => __('Cart'), 'href' => store_route('cart'), 'icon' => 'M5.5 8h13l-1 12.5h-11ZM9 8V6.5a3 3 0 0 1 6 0V8', 'active' => request()->routeIs('cart', '*.cart'), 'cart' => true],
        ['label' => __('Account'), 'href' => $customer ? store_route('account.dashboard') : store_route('login'), 'icon' => 'M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4.5 20.5c1.4-3.6 4.2-5.5 7.5-5.5s6.1 1.9 7.5 5.5', 'active' => request()->routeIs('account.*', '*.account.*', 'login', '*.login')],
    ];
  @endphp
  <nav class="mob-only" aria-label="{{ __('Shortcuts') }}" style="position:fixed; left:0; right:0; bottom:0; z-index:50; background:#FFFFFF; border-top:1px solid var(--fn-line); box-shadow:0 -6px 20px rgba(25,75,48,.08); padding:8px 6px calc(8px + env(safe-area-inset-bottom));">
    @foreach ($bottomNav as $b)
      <a href="{{ $b['href'] }}" @if (! empty($b['cart'])) x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count" @click.prevent="$dispatch('open-cart-drawer')" @endif style="flex:1 1 0; display:flex; flex-direction:column; align-items:center; gap:5px; padding:6px 2px; min-height:52px; position:relative; color:{{ $b['active'] ? 'var(--fn-primary)' : 'var(--fn-muted)' }};">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $b['icon'] }}"></path></svg>
        <span style="font-size:11px; font-weight:600;">{{ $b['label'] }}</span>
        @if (! empty($b['cart']))
          <span style="position:absolute; top:2px; right:calc(50% - 22px); min-width:18px; height:18px; padding:0 5px; border-radius:9px; background:var(--fn-accent); color:#FFFFFF; font-size:10.5px; font-weight:700; display:inline-flex; align-items:center; justify-content:center;" x-text="count">{{ $cartCount }}</span>
        @endif
      </a>
    @endforeach
  </nav>
