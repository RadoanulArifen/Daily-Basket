{{--
    FreshNest header — markup and styles from the design (freshnest-html, every page):
    the thin top bar, the white header (logo, search, account / wishlist / cart) and the
    navigation row (All Categories, the header menu, the deals menu).

    Where the words come from (nothing here is fixed text the owner cannot change):
      top bar text      Settings → Store details → announcement, else the free-delivery threshold
      logo              Settings → Store details → logo, else the design's leaf mark + store name
      All Categories    Catalog → Categories (top level, with their images)
      menu row          Content → Menus → "header" (items with sub-items open a dropdown)
      "Super Discount"  Content → Menus → "header-deals": the menu name is "small line|big line",
                        each item's label is "text|badge" (e.g. "Fresh dairy|BUY 2 GET 1")
    The design's Compare button and "Your Location" picker have no store feature behind them
    and are left out.
--}}
@php
    $settingsSvc = settings();
    $delivery = app(\App\Themes\Freshnest\Delivery::class);
    $freeAt = $delivery->freeThreshold();
    $topText = $settingsSvc->bool('general.announcement_enabled') && filled($settingsSvc->trans('general.announcement_text'))
        ? $settingsSvc->trans('general.announcement_text')
        : ($freeAt ? __('Free delivery on orders over :amount', ['amount' => price_whole($freeAt)]) : null);

    $uploads = \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'));
    $logo = ($f = $settingsSvc->get('general.logo')) && $f !== \Database\Seeders\FreshnestSeeder::LOGO_PATH ? $uploads->url($f) : null; // the bundled logo = the design's own drawn mark

    $menus = app(\App\Services\Navigation\MenuRepository::class);
    $mainMenu = $menus->get('header')?->tree ?? collect();
    $dealsMenu = $menus->get('header-deals');
    [$dealsSmall, $dealsBig] = array_pad(explode('|', (string) ($dealsMenu?->name ?? ''), 2), 2, '');
    $dealsItems = $dealsMenu?->tree ?? collect();

    $navCategories = \App\Models\Category::query()->active()->whereNull('parent_id')->with('image')->orderBy('sort_order')->get();

    $customer = auth('web')->user();
    $initials = $customer ? collect(preg_split('/\s+/', trim((string) $customer->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') : '';
    $orderCount = $customer ? $customer->orders()->count() : 0;

    $here = '/'.trim(request()->path(), '/');
    $isHere = function (string $href) use ($here): bool {
        $path = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');

        return $path === $here && ! str_contains($href, '#');
    };
    // Phone menu: the top-level links, then the categories under any of them.
    $mobileLinks = $mainMenu->flatMap(fn ($item) => collect([$item])->concat($item->children->filter(fn ($c) => str_starts_with((string) $c->url, '/collections/'))));
@endphp
<div x-data="{ menuOpen: false, catsOpen: false }" @keydown.escape.window="menuOpen = false; catsOpen = false" style="display:contents;">
  @if (filled($topText) || count(app(\App\Support\Localization::class)->locales()) > 1 || app(\App\Services\Currency\CurrencyService::class)->active()->count() > 1)
  <div style="background:var(--fn-surface-muted); border-bottom:1px solid var(--fn-line); font-size:13px; color:#5E656B;">
    <div class="wrap" style="display:flex; align-items:center; justify-content:space-between; gap:16px; min-height:38px;">
      @if (filled($topText))<span style="display:flex; align-items:center; gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 7h10.5v9H2.5"></path><path d="M13 10h4.2l3.3 3.3V16H13"></path><circle cx="6.5" cy="17" r="1.8"></circle><circle cx="17" cy="17" r="1.8"></circle></svg>{{ $topText }}</span>@else<span></span>@endif
      <div style="display:flex; align-items:center; gap:22px;">
        @include('storefront.partials.switchers', ['on' => 'light'])
        <div class="h2-desk" style="align-items:center; gap:22px;">
        <a href="{{ $customer ? store_route('account.orders') : store_route('login') }}" style="display:flex; align-items:center; gap:6px; color:#5E656B;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 7h10.5v9H2.5"></path><path d="M13 10h4.2l3.3 3.3V16H13"></path><circle cx="6.5" cy="17" r="1.8"></circle><circle cx="17" cy="17" r="1.8"></circle></svg>{{ __('Order Tracking') }}</a>
        <a href="{{ store_route('pages.show', ['slug' => 'contact']) }}" style="display:flex; align-items:center; gap:6px; color:#5E656B;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--fn-primary)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M9.6 9.5a2.5 2.5 0 0 1 4.8.9c0 1.7-2.4 2-2.4 3.6"></path><path d="M12 17h.01"></path></svg>{{ __('Help Center') }}</a>
        </div>
      </div>
    </div>
  </div>
  @endif

  <header style="position:sticky; top:0; z-index:40; background:#FFFFFF; border-bottom:1px solid var(--fn-line);">
    <div class="wrap" style="display:flex; align-items:center; gap:clamp(12px,2.4vw,40px); padding-top:16px; padding-bottom:16px;">
      <button type="button" class="h2-mob" @click="menuOpen = ! menuOpen" :aria-expanded="menuOpen" aria-controls="fn-mobile-menu" aria-label="{{ __('Menu') }}" style="width:42px; height:42px; border-radius:12px; border:1px solid var(--fn-line); background:#FFFFFF; align-items:center; justify-content:center; cursor:pointer; flex:0 0 auto;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"></path></svg></button>
      <a href="{{ store_route('home') }}" aria-label="{{ $storeName }}" style="display:flex; align-items:center; gap:10px; flex:0 0 auto;">
        @if ($logo)
          <img src="{{ $logo }}" alt="{{ $storeName }}" style="height:38px; width:auto; max-width:220px; object-fit:contain;">
        @else
        <img src="{{ asset('themes/freshnest/favicon.png') }}?v=2" alt="" width="38" height="38">
        <span style="font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; letter-spacing:-.02em; ; color:var(--fn-primary)">{{ $storeName }}</span>
        @endif
      </a>
      <form method="GET" action="{{ store_route('search') }}" role="search" data-tour="store-search" class="h2-desk" style="flex:1 1 auto; max-width:760px; margin-left:auto; margin-right:auto; align-items:center; height:54px; border:1px solid #E4E6E2; border-radius:999px; background:#FFFFFF; padding:4px 5px 4px 0;">
        <label title="{{ __('Search by category') }}" style="position:relative; flex:0 0 auto; display:flex; align-items:center; justify-content:center; width:62px; height:100%; border-right:1px solid var(--fn-line); cursor:pointer;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M3 5.5h18M3 10h18M3 14.5h8M3 19h8"></path><circle cx="16.5" cy="16.5" r="2.8"></circle><path d="m18.6 18.6 2.2 2.2"></path></svg>
          <select name="cat[]" aria-label="{{ __('Category') }}" style="position:absolute; inset:0; opacity:0; cursor:pointer; font-size:14px;">
            <option value="">{{ __('All Categories') }}</option>@foreach ($navCategories as $category)<option value="{{ $category->id }}" @selected(in_array($category->id, array_map('intval', (array) request('cat', []))))>{{ $category->name }}</option>@endforeach
          </select>
        </label>
        <input name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Search for product...') }}" aria-label="{{ __('store.search.label') }}" style="flex:1 1 auto; min-width:0; height:100%; border:0; outline:none; padding:0 18px; font-size:14.5px; color:var(--fn-ink); background:transparent;" />
        <button class="fh-f80239" type="submit" aria-label="{{ __('Search') }}" style="flex:0 0 auto; height:100%; padding:0 22px; border:0; border-radius:999px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>{{ __('Search') }}</button>
      </form>
      <div style="margin-left:auto; display:flex; align-items:flex-end; gap:clamp(10px,1.6vw,26px);">
        <div class="nv h2-desk" style="position:relative; align-self:stretch; align-items:flex-end;">
          @guest('web')
            <a class="fh-be0098" href="{{ store_route('login') }}" aria-label="{{ __('Account') }}" style="display:flex; flex-direction:column; align-items:center; gap:5px; min-width:48px; color:var(--fn-ink);">
          <span style="position:relative; display:inline-flex;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4.5 20.5c1.4-3.6 4.2-5.5 7.5-5.5s6.1 1.9 7.5 5.5"></path></svg></span>
          <span class="h2-desk" style="font-size:12.5px; font-weight:500;">{{ __('Account') }}</span>
        </a>
          @else
            <a class="fh-99d6f6" href="{{ store_route('account.dashboard') }}" aria-label="{{ __('My account') }}" style="display:flex; flex-direction:column; align-items:center; gap:5px; min-width:48px; color:var(--fn-ink);">
              <span style="position:relative; display:inline-flex; width:28px; height:28px; border-radius:50%; background:var(--fn-primary); color:#FFFFFF; font-size:11.5px; font-weight:800; align-items:center; justify-content:center; box-shadow:0 0 0 2px #FFFFFF, 0 0 0 3.5px var(--fn-primary);">{{ $initials }}<span style="position:absolute; right:-2px; bottom:-2px; width:9px; height:9px; border-radius:50%; background:#34C759; border:2px solid #FFFFFF;"></span></span>
              <span style="font-size:12.5px; font-weight:600; white-space:nowrap;">{{ __('Hi,') }} {{ \Illuminate\Support\Str::before(trim((string) $customer->name).' ', ' ') }}</span>
            </a>
            <div class="nv-panel" style="position:absolute; right:-12px; top:100%; z-index:80; padding-top:10px; width:280px;">
              <div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:16px; box-shadow:0 22px 44px rgba(30,35,40,.16); overflow:hidden;">
                <div style="display:flex; align-items:center; gap:12px; padding:16px; background:var(--fn-primary-tint);">
                  <span style="display:inline-flex; width:46px; height:46px; flex:0 0 auto; border-radius:50%; background:var(--fn-primary); color:#FFFFFF; font-weight:800; font-size:16px; align-items:center; justify-content:center;">{{ $initials }}</span>
                  <span style="min-width:0; line-height:1.35;">
                    <strong style="display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:15px; color:var(--fn-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $customer->name }}</strong>
                    <span style="display:block; font-size:12.5px; color:#5E656B; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $customer->email }}</span>
                  </span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); border-bottom:1px solid var(--fn-line);">
                  <a class="fh-a9cef1" href="{{ store_route('account.orders') }}" style="display:flex; flex-direction:column; align-items:center; padding:12px 4px; color:var(--fn-ink);"><strong style="font-size:16px;">{{ $orderCount }}</strong><span style="font-size:11.5px; color:#6F767D;">{{ __('Orders') }}</span></a>
                  <a class="fh-a9cef1" href="{{ store_route('wishlist') }}" x-data="{ count: {{ $wishlistCount }} }" @wishlist-updated.window="count = $event.detail.count" style="display:flex; flex-direction:column; align-items:center; padding:12px 4px; border-left:1px solid var(--fn-line); border-right:1px solid var(--fn-line); color:var(--fn-ink);"><strong style="font-size:16px;" x-text="count">{{ $wishlistCount }}</strong><span style="font-size:11.5px; color:#6F767D;">{{ __('Wishlist') }}</span></a>
                  <a class="fh-a9cef1" href="{{ store_route('cart') }}" x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count" @click.prevent="$dispatch('open-cart-drawer')" style="display:flex; flex-direction:column; align-items:center; padding:12px 4px; color:var(--fn-ink);"><strong style="font-size:16px;" x-text="count">{{ $cartCount }}</strong><span style="font-size:11.5px; color:#6F767D;">{{ __('In cart') }}</span></a>
                </div>
                <div style="display:flex; flex-direction:column; gap:2px; padding:8px;">
                  <a class="fh-74ffb8" href="{{ store_route('account.dashboard') }}" style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4.5 20.5c1.4-3.6 4.2-5.5 7.5-5.5s6.1 1.9 7.5 5.5"></path></svg>{{ __('My Profile') }}</a>
                  <a class="fh-74ffb8" href="{{ store_route('account.orders') }}" style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12l1 5H5Z"></path><path d="M5 8v12h14V8"></path><path d="M9 12h6"></path></svg>{{ __('My Orders') }}</a>
                  <a class="fh-74ffb8" href="{{ store_route('wishlist') }}" style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20Z"></path></svg>{{ __('Wishlist') }}</a>
                  <a class="fh-74ffb8" href="{{ store_route('account.addresses') }}" style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"></path><circle cx="12" cy="9.5" r="2.5"></circle></svg>{{ __('Saved Addresses') }}</a>
                </div>
                <form method="POST" action="{{ store_route('logout') }}">
                  @csrf
                  <button class="fh-e15e24" type="submit" style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; height:46px; border:0; border-top:1px solid var(--fn-line); background:#FFFFFF; color:#D93A2B; font-weight:700; font-size:14px; cursor:pointer;"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"></path><path d="M10 17l-5-5 5-5"></path><path d="M5 12h11"></path></svg>{{ __('Sign out') }}</button>
                </form>
              </div>
            </div>
          @endguest
        </div>
        <a class="fh-be0098" href="{{ store_route('wishlist') }}" x-data="{ count: {{ $wishlistCount }} }" @wishlist-updated.window="count = $event.detail.count" aria-label="{{ __('Wishlist') }}" style="display:flex; flex-direction:column; align-items:center; gap:5px; min-width:48px; color:var(--fn-ink);">
          <span style="position:relative; display:inline-flex;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7.5 2.8C19.5 15.4 12 20 12 20Z"></path></svg><span style="position:absolute; top:-6px; right:-10px; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-size:10.5px; font-weight:700; display:flex; align-items:center; justify-content:center; border:2px solid #FFFFFF;" x-text="count">{{ $wishlistCount }}</span></span>
          <span class="h2-desk" style="font-size:12.5px; font-weight:500;">{{ __('Wishlist') }}</span>
        </a>
        <a class="fh-be0098" href="{{ store_route('cart') }}" data-cart-link x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count" @click.prevent="$dispatch('open-cart-drawer')" aria-label="{{ __('Cart') }}" style="display:flex; flex-direction:column; align-items:center; gap:5px; min-width:48px; color:var(--fn-ink);">
          <span style="position:relative; display:inline-flex;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8h13l-1 12.5h-11Z"></path><path d="M9 8V6.5a3 3 0 0 1 6 0V8"></path></svg><span style="position:absolute; top:-6px; right:-10px; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:var(--fn-primary); color:#FFFFFF; font-size:10.5px; font-weight:700; display:flex; align-items:center; justify-content:center; border:2px solid #FFFFFF;" x-text="count">{{ $cartCount }}</span></span>
          <span class="h2-desk" style="font-size:12.5px; font-weight:500;">{{ __('Cart') }}</span>
        </a>
      </div>
    </div>

    <div class="wrap h2-mob" style="padding-bottom:12px;">
      <form method="GET" action="{{ store_route('search') }}" role="search" data-tour="store-search" style="flex:1 1 auto; display:flex; align-items:center; height:46px; border:1.5px solid #E4E6E2; border-radius:999px; padding:4px 4px 4px 16px; gap:8px;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#6F767D" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
        <input name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Search products…') }}" aria-label="{{ __('store.search.label') }}" style="flex:1 1 auto; min-width:0; border:0; outline:none; font-size:14.5px; background:transparent;" />
        <button type="submit" style="height:100%; padding:0 16px; border:0; border-radius:999px; background:var(--fn-accent); color:#FFFFFF; font-weight:700; font-size:13.5px;">{{ __('Go') }}</button>
      </form>
    </div>

    <div class="h2-desk" style="background:transparent; border-top:1px solid var(--fn-line);">
      <div class="wrap" style="padding-top:8px; padding-bottom:8px;"><div style="display:flex; align-items:center; gap:28px; min-height:64px;">
        <div class="nv-cat" data-tour="store-categories" style="position:relative; align-self:stretch;" @click.outside="catsOpen = false">
          <button class="fh-92c14d" type="button" @click="catsOpen = ! catsOpen" :aria-expanded="catsOpen" style="border-radius:999px; width:100%; height:100%; display:flex; align-items:center; gap:12px; padding:0 18px; border:0; background:var(--fn-primary); color:#FFFFFF; font-weight:700; font-size:14.5px; cursor:pointer;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
            {{ __('All Categories') }}
            <svg style="margin-left:auto;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
          </button>
          <div x-show="catsOpen" x-cloak>
            <div style="position:absolute; left:0; right:0; top:100%; z-index:60; background:#FFFFFF; border:1px solid var(--fn-line); border-top:0; border-radius:0 0 14px 14px; box-shadow:0 20px 40px rgba(30,35,40,.1); padding:6px 0;">
              @foreach ($navCategories as $category)
                <a class="fn-category-link" href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" @if ($isHere(store_route('collections.show', ['slug' => $category->slug]))) aria-current="page" @endif style="display:flex; align-items:center; gap:12px; padding:10px 18px; font-size:14px; font-weight:500;">
                  <span style="width:28px; height:28px; border-radius:8px; background:var(--fn-surface-muted){{ $category->image ? ' url('.$category->image->url('thumb').')' : '' }} center/cover no-repeat; flex:0 0 auto;"></span>{{ $category->name }}
                </a>
              @endforeach
            </div>
          </div>
        </div>
        <nav class="nv-links" aria-label="{{ __('Main') }}" style="display:flex; align-items:center; height:64px;">
          @foreach ($mainMenu as $item)
            @php $has = $item->children->isNotEmpty(); @endphp
            <div class="nv" style="position:relative; height:100%; display:flex; align-items:center;">
              <a class="fh-99d6f6" href="{{ $item->href() }}" @if ($isHere($item->href())) aria-current="page" @endif @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif style="display:inline-flex; align-items:center; gap:6px; font-size:14px; font-weight:700; letter-spacing:.02em; text-transform:uppercase; color:{{ $isHere($item->href()) ? 'var(--fn-primary)' : 'var(--fn-ink)' }};">{{ $item->label }}@if ($has)<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>@endif</a>
              @if ($has)
                <div class="nv-panel" style="position:absolute; left:-14px; top:100%; z-index:70; padding-top:8px; min-width:220px;"><div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:14px; box-shadow:0 20px 40px rgba(30,35,40,.14); padding:6px; display:flex; flex-direction:column; gap:2px;">@foreach ($item->children as $child)<a class="fh-74ffb8" href="{{ $child->href() }}" @if ($child->open_in_new_tab) target="_blank" rel="noopener" @endif style="display:block; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink); white-space:nowrap;">{{ $child->label }}</a>@endforeach</div></div>
              @endif
            </div>
          @endforeach
        </nav>
        @if ($dealsMenu)
        <div style="margin-left:auto; display:flex; align-items:center; gap:clamp(18px,2.4vw,40px); height:64px;">
          <div class="nv nv-mid" style="position:relative; height:100%; align-items:center;">
            <a class="fh-72d217" href="{{ store_route('pages.show', ['slug' => 'offers']) }}" style="display:flex; align-items:center; gap:10px; color:var(--fn-ink);">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--fn-accent)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.8 14.2 4.4 16.9 4.2 17.8 6.8 20.2 8 19.9 10.7 21.2 13 19.4 15 19.2 17.7 16.5 18.3 14.8 20.4 12 19.6 9.2 20.4 7.5 18.3 4.8 17.7 4.6 15 2.8 13 4.1 10.7 3.8 8 6.2 6.8 7.1 4.2 9.8 4.4Z"></path><path d="m9.3 14.7 5.4-5.4"></path><circle cx="9.6" cy="9.6" r=".9"></circle><circle cx="14.4" cy="14.4" r=".9"></circle></svg>
              <span style="line-height:1.25;"><span style="display:block; font-size:12px; color:#6F767D;">{{ __(trim($dealsSmall)) }}</span><strong style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700;">{{ __(trim($dealsBig) ?: trim($dealsSmall)) }}@if ($dealsItems->isNotEmpty())<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--fn-ink)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>@endif</strong></span>
            </a>
            @if ($dealsItems->isNotEmpty())
            <div class="nv-panel" style="position:absolute; right:0; top:100%; z-index:70; padding-top:8px; min-width:220px;"><div style="background:#FFFFFF; border:1px solid var(--fn-line); border-radius:14px; box-shadow:0 20px 40px rgba(30,35,40,.14); padding:6px; display:flex; flex-direction:column; gap:2px;">@foreach ($dealsItems as $deal)@php [$dealText, $dealTag] = array_pad(explode('|', (string) $deal->label, 2), 2, ''); @endphp<a class="fh-b8e786" href="{{ $deal->href() }}" style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500; color:var(--fn-ink); white-space:nowrap;">{{ trim($dealText) }}@if (filled(trim($dealTag)))<span style="padding:3px 8px; border-radius:6px; background:var(--fn-accent); color:#FFFFFF; font-size:11.5px; font-weight:700;">{{ trim($dealTag) }}</span>@endif</a>@endforeach</div></div>
            @endif
          </div>
        </div>
        @endif
      </div>
      </div>
    </div>

    <div id="fn-mobile-menu" x-show="menuOpen" x-cloak>
      <div class="h2-mob" style="flex-direction:column; border-top:1px solid var(--fn-line); background:#FFFFFF; padding:8px 16px 16px;">
        @foreach ($mobileLinks as $item)
          <a href="{{ $item->href() }}" @if ($isHere($item->href())) aria-current="page" @endif style="display:flex; align-items:center; justify-content:space-between; min-height:48px; border-bottom:1px solid #F0F1EE; font-weight:600; font-size:15px; color:{{ $isHere($item->href()) ? 'var(--fn-primary)' : 'var(--fn-ink)' }};">{{ $item->label }}<span style="color:#A3A9AE;">›</span></a>
        @endforeach
      </div>
    </div>
  </header>
</div>
