@php
    // A short message across the top, switched on in Settings → Store details.
    $announcement = settings()->bool('general.announcement_enabled') ? settings()->trans('general.announcement_text') : null;

    // The shop's own logos (Settings → Store details); the design's own are the fallback.
    $uploads = \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'));
    $logoDark = ($f = settings()->get('general.logo')) ? $uploads->url($f) : asset('themes/furnivo/furnivo-logo-black.png');
    $logoWhite = ($f = settings()->get('general.logo_white')) ? $uploads->url($f) : asset('themes/furnivo/furnivo-logo-white.png');
@endphp

{{--
    Furnivo header — markup and styles from the design (site.zip). Transparent over a hero,
    solid once scrolled (furnivoHeader in js/header.js); only the properties that differ
    between the two states are bound.
--}}
@php
    $navCategories = \App\Models\Category::query()->active()->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->active(), 'image'])->orderBy('sort_order')->limit(6)->get();
    $W = 'rgb(255, 255, 255)'; $D = 'rgb(32, 32, 32)';
@endphp
<header x-data="furnivoHeader({ overHero: {{ $overHero ? 'true' : 'false' }} })" @mouseleave="megaOpen = null"
        style="position: fixed; top: 0px; left: 0px; right: 0px; z-index: 50; transition: background 500ms, border-color 500ms; backdrop-filter: blur(10px);"
        :style="dark ? { background: 'rgba(20, 20, 20, 0.18)', borderBottom: '1px solid rgba(255, 255, 255, 0.14)' } : { background: 'rgba(255, 255, 255, 0.92)', borderBottom: '1px solid rgb(236, 236, 236)' }">
    @if (filled($announcement))
        <div class="fv-announce" style="background: rgb(29, 29, 29); color: rgb(255, 255, 255); text-align: center; padding: 0px 16px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <p style="margin: 0px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;">{{ $announcement }}</p>
        </div>
    @endif

    <div class="util-bar fx-shell" style="height: 64px; display: flex; align-items: center; gap: 24px; padding: 0px clamp(24px, 4.5vw, 80px); transition: color 500ms;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
        <a href="{{ store_route('home') }}" class="util-logo" style="cursor: pointer; position: relative; width: 322px; height: 92px; flex-shrink: 0;" aria-label="{{ $storeName }}">
            <img alt="{{ $storeName }}" src="{{ $logoDark }}" style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: contain; object-position: left center; transition: opacity 500ms;" :style="{ opacity: dark ? 0 : 1 }">
            <img alt="" aria-hidden="true" src="{{ $logoWhite }}" style="position: absolute; inset: 0px; width: 100%; height: 100%; object-fit: contain; object-position: left center; transition: opacity 500ms;" :style="{ opacity: dark ? 1 : 0 }">
        </a>
        <div class="util-search" style="flex: 1 1 0%; max-width: 560px; margin: 0px auto; position: relative;"
             x-data="window.liveSearch
                        ? window.liveSearch('{{ store_route('search.suggest') }}')
                        : { term: '', results: [], suggestion: null, allUrl: null, open: false, busy: false, active: -1,
                            look() {}, move() {}, choose() { return true }, useSuggestion() {}, close() {} }"
             @click.outside="close()" @keydown.escape="close()">
            <form method="GET" action="{{ store_route('search') }}" role="search" data-tour="store-search" style="display: flex; align-items: center; gap: 12px; padding: 12px 18px; transition: background 400ms, border-color 400ms;"
                  :style="dark ? { background: 'rgba(20, 20, 20, 0.38)', border: '1.5px solid rgba(255, 255, 255, 0.55)' } : { background: 'rgb(244, 242, 238)', border: '1.5px solid rgb(207, 202, 194)' }">
                <span style="display: flex; align-items: center; justify-content: center; flex-shrink: 0; opacity: 0.85;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
                    <svg fill="none" height="21" stroke="currentColor" stroke-linecap="round" stroke-width="1.8" viewBox="0 0 24 24" width="21" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M16.5 16.5L21 21"></path></svg>
                </span>
                <label for="fv-search" class="fv-sr">{{ __('store.search.label') }}</label>
                <input id="fv-search" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('store.theme.search_placeholder') }}" autocomplete="off"
                       x-model="term" @input="look()" @focus="term.trim().length >= 2 && (open = true)"
                       @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter="if (! choose()) $event.preventDefault()"
                       role="combobox" aria-autocomplete="list" aria-controls="fv-search-results" :aria-expanded="open"
                       style="flex: 1 1 0%; border: none; background: transparent; font-size: 15px; letter-spacing: 0.01em; outline: none;"
                       :class="dark ? 'ser-search-dark' : 'ser-search-light'" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
            </form>

            {{-- Results while you type. The form still works without any of this. --}}
            <div id="fv-search-results" x-show="open && (busy || results.length > 0 || suggestion || term.trim().length >= 2)" x-cloak @mousedown.prevent
                 role="listbox" aria-label="{{ __('store.search.label') }}"
                 style="position: absolute; top: calc(100% + 8px); inset-inline: 0; background: rgb(255, 255, 255); border: 1px solid rgb(236, 236, 236); box-shadow: 0 24px 50px rgba(0,0,0,.14); z-index: 40; max-height: 70vh; overflow-y: auto;">
                <template x-for="(result, i) in results" :key="result.url">
                    <a :href="result.url" role="option" :aria-selected="active === i"
                       :style="{ background: active === i ? 'rgb(245, 244, 240)' : 'transparent' }"
                       style="display: flex; align-items: center; gap: 14px; padding: 12px 16px; text-decoration: none; color: rgb(32, 32, 32); border-bottom: 1px solid rgb(244, 243, 240);">
                        <template x-if="result.image">
                            <img :src="result.image" alt="" loading="lazy" style="width: 48px; height: 48px; object-fit: cover; flex-shrink: 0; background: rgb(245, 244, 240);">
                        </template>
                        <span style="flex: 1 1 0%; min-width: 0px; font-size: 14px;" x-text="result.name"></span>
                        <span style="font-size: 13px; color: rgb(139, 135, 127);" x-text="result.price"></span>
                    </a>
                </template>

                <template x-if="! busy && results.length === 0 && suggestion">
                    <button type="button" @click="useSuggestion()"
                            style="display: block; width: 100%; padding: 14px 16px; border: none; background: transparent; text-align: start; font-size: 14px; cursor: pointer; color: rgb(32, 32, 32);">
                        {{ __('store.search.did_you_mean') }} <strong x-text="suggestion"></strong>
                    </button>
                </template>

                <template x-if="! busy && results.length === 0 && ! suggestion">
                    <p style="margin: 0px; padding: 16px; font-size: 14px; color: rgb(139, 135, 127);">{{ __('store.search.nothing') }}</p>
                </template>

                <template x-if="results.length > 0">
                    <a :href="allUrl" style="display: block; padding: 12px 16px; font-size: 12px; letter-spacing: 0.12em; text-transform: uppercase; text-decoration: none; color: rgb(32, 32, 32);">{{ __('store.search.see_all') }}</a>
                </template>
            </div>
        </div>
        <nav class="util-actions" style="display: flex; align-items: center; gap: 14px; flex-shrink: 0;" aria-label="{{ __('store.nav.account') }}">
            {{-- Phones: the categories live behind this, since the row of names does not fit. --}}
            <button type="button" class="util-menu-btn" @click="toggleMenu()" :aria-expanded="menuOpen"
                    aria-controls="fv-mobile-menu" aria-label="{{ __('store.nav.open_menu') }}"
                    style="border: none; background: transparent; color: inherit; cursor: pointer; width: 38px; height: 38px; display: none; align-items: center; justify-content: center; padding: 0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path x-show="! menuOpen" d="M3 6h18M3 12h18M3 18h18"></path>
                    <path x-show="menuOpen" x-cloak d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>

            {{-- Language, only when the shop runs in more than one. Keeps the visitor on
                 the page they are reading. --}}
            @php
                $localization = app(\App\Support\Localization::class);
                $languages = $localization->locales();
                $herePath = '/'.ltrim(preg_replace('#^/'.preg_quote($localization->current(), '#').'(/|$)#', '/', '/'.ltrim(request()->path(), '/')), '/');
                $query = request()->getQueryString();
            @endphp
            @if (count($languages) > 1)
                {{-- Same shape as the currency picker beside it: a select that navigates on
                     change, so it works without JavaScript being involved in the choice. --}}
                <form method="GET" action="{{ $localization->url($herePath, $localization->default()) }}" x-data
                      @submit.prevent
                      style="display: flex; align-items: center;">
                    <label for="language-switch" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">{{ __('store.common.language') }}</label>
                    <select id="language-switch" name="lang"
                            @change="window.location = $event.target.value"
                            style="appearance: none; border: none; background: transparent; color: inherit; font-size: 12px; letter-spacing: 0.08em; cursor: pointer; padding: 6px 2px;">
                        @foreach ($languages as $code => $meta)
                            <option value="{{ $localization->url($herePath, $code).($query ? '?'.$query : '') }}"
                                    @selected($code === $localization->current())>{{ strtoupper($code) }}</option>
                        @endforeach
                    </select>
                    {{-- Without JavaScript the select does nothing, so keep plain links too. --}}
                    <noscript>
                        @foreach ($languages as $code => $meta)
                            <a href="{{ $localization->url($herePath, $code) }}" hreflang="{{ $code }}"
                               style="color: inherit; opacity: {{ $code === $localization->current() ? '.95' : '.55' }}; text-decoration: none; font-size: 12px; margin-inline-end: 6px;">{{ strtoupper($code) }}</a>
                        @endforeach
                    </noscript>
                </form>
            @endif

            {{-- Currency, only when the store actually offers more than one. --}}
            @php $currencies = app(\App\Services\Currency\CurrencyService::class); $available = $currencies->active(); @endphp
            @if ($available->count() > 1)
                <form method="POST" action="{{ route('currency.switch') }}" x-data style="display: flex; align-items: center;">
                    @csrf
                    <label for="currency-switch" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">{{ __('store.common.currency') }}</label>
                    <select id="currency-switch" name="currency" @change="$el.form.submit()"
                            style="appearance: none; border: none; background: transparent; color: inherit; font-size: 12px; letter-spacing: 0.08em; cursor: pointer; padding: 6px 2px;">
                        @foreach ($available as $currency)
                            <option value="{{ $currency->code }}" @selected($currency->code === $currencies->current()->code)>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <a href="{{ auth('web')->check() ? store_route('account.dashboard') : store_route('login') }}" aria-label="{{ auth('web')->check() ? __('store.nav.my_account') : __('store.auth.login') }}" title="{{ auth('web')->check() ? __('store.nav.my_account') : __('store.auth.login') }}"
               class="util-account scp1" style="border: none; background: transparent; cursor: pointer; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; padding: 0px; transition: opacity 200ms;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
                <svg fill="none" height="21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="21" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
            </a>
            <a href="{{ store_route('wishlist') }}" aria-label="{{ __('store.nav.wishlist') }}" title="{{ __('store.nav.wishlist') }}" x-data="{ count: {{ $wishlistCount }} }" @wishlist-updated.window="count = $event.detail.count"
               class="util-wish scp1" style="border: none; background: transparent; cursor: pointer; height: 34px; display: flex; align-items: center; gap: 5px; padding: 0px; transition: opacity 200ms;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
                <svg fill="none" height="21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="21" aria-hidden="true"><path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1l1.7 1.7L12 21.5l7.1-7.1 1.7-1.7a5 5 0 0 0 0-7.1z"></path></svg>
                <span style="font-size: 12px; opacity: 0.75; min-width: 8px;"><span x-text="count">{{ $wishlistCount }}</span></span>
            </a>
            <a href="{{ store_route('cart') }}" data-cart-link aria-label="{{ __('store.nav.cart', ['count' => $cartCount]) }}" :aria-label="@js(__('store.nav.cart', ['count' => '__N__'])).replace('__N__', count)" title="{{ __('store.cart.title') }}" x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count" @click.prevent="$dispatch('open-cart-drawer')"
               class="scp1" style="position: relative; border: none; background: transparent; cursor: pointer; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; padding: 0px; transition: opacity 200ms;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }">
                <svg fill="none" height="21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="21" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span style="position: absolute; top: -4px; right: -6px; display: inline-flex; align-items: center; justify-content: center; min-width: 17px; height: 17px; padding: 0px 4px; font-size: 10px; font-weight: 600;"
                      :style="dark ? { background: '{{ $W }}', color: 'rgb(29, 29, 29)' } : { background: 'rgb(29, 29, 29)', color: '{{ $W }}' }"><span x-text="count">{{ $cartCount }}</span></span>
            </a>
            <a href="{{ store_route('custom-order') }}" @click.prevent="$dispatch('open-custom-order')" class="custom-order-btn scp2" style="margin-left: 6px; font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; padding: 11px 20px; cursor: pointer; white-space: nowrap; transition: opacity 300ms;"
               :style="dark ? { border: '1px solid {{ $W }}', background: '{{ $W }}', color: 'rgb(29, 29, 29)' } : { border: '1px solid {{ $D }}', background: '{{ $D }}', color: '{{ $W }}' }">{{ __('store.theme.custom_order') }}</a>
        </nav>
    </div>

    {{-- Category bar + mega menu --}}
    <div style="position: relative; height: 56px;" :style="{ borderTop: dark ? '1px solid rgba(255, 255, 255, 0.14)' : '1px solid rgb(236, 236, 236)' }"
         x-data="{ bar: { x: 0, w: 0, on: false } }" @mouseleave="bar.on = false">
        <nav class="main-nav-scroll fx-shell" aria-label="{{ __('store.nav.shop') }}" style="height: 100%; display: flex; align-items: center; justify-content: center; gap: clamp(44px, 7vw, 104px); padding: 0px clamp(24px, 4.5vw, 80px); position: relative;">
            @foreach ($navCategories as $category)
                <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" style="position: relative; height: 56px; display: flex; align-items: center; cursor: pointer;"
                   @mouseenter="megaOpen = {{ $category->children->isNotEmpty() ? $category->id : 'null' }}; bar = { x: $el.offsetLeft, w: $el.offsetWidth, on: true }" @focus="megaOpen = {{ $category->id }}">
                    {{-- The category being browsed is shown in bold, as in the design. --}}
                    @php
                        $slugs = collect([$category->slug])->concat($category->children->pluck('slug'));
                        $isCurrent = $slugs->contains(fn ($slug) => request()->is(trim(parse_url(store_route('collections.show', ['slug' => $slug]), PHP_URL_PATH), '/')));
                    @endphp
                    <span style="font-size: 13px; letter-spacing: 0.16em; text-transform: uppercase; font-weight: {{ $isCurrent ? 700 : 500 }}; transition: color 500ms;" :style="{ color: dark ? '{{ $W }}' : '{{ $D }}' }"><span>{{ $category->name }}</span></span>
                </a>
            @endforeach
            <div aria-hidden="true" style="position: absolute; bottom: 0px; left: 0px; height: 2px; transition: transform 420ms cubic-bezier(0.22, 1, 0.36, 1), width 420ms cubic-bezier(0.22, 1, 0.36, 1), opacity 300ms;"
                 :style="{ background: dark ? '{{ $W }}' : '{{ $D }}', width: bar.w + 'px', transform: 'translateX(' + bar.x + 'px)', opacity: bar.on ? 1 : 0 }"></div>
        </nav>

        @foreach ($navCategories->filter(fn ($c) => $c->children->isNotEmpty()) as $category)
            <div x-show="megaOpen === {{ $category->id }}" x-cloak style="position: absolute; top: 56px; left: 0px; right: 0px; background: rgb(255, 255, 255); border-top: 1px solid rgb(236, 236, 236); border-bottom: 1px solid rgb(236, 236, 236); box-shadow: rgba(0, 0, 0, 0.32) 0px 30px 50px -30px; animation: 300ms ease 0s 1 normal both running serFade;">
                <div style="width: 100%; padding: 44px clamp(24px, 4.5vw, 80px) 48px; display: grid; grid-template-columns: 1.5fr 1fr; gap: clamp(40px, 5vw, 72px); align-items: center;">
                    <div>
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 26px; border-bottom: 1px solid rgb(236, 236, 236); padding-bottom: 16px;">
                            <div style="font-size: 11px; letter-spacing: 0.24em; text-transform: uppercase; color: rgb(139, 135, 127);">{{ __('store.theme.shop') }}<span>{{ $category->name }}</span></div>
                            <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" class="scp3" style="cursor: pointer; font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: rgb(29, 29, 29); white-space: nowrap; transition: opacity 200ms;">View all →</a>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2px 40px;">
                            @foreach ($category->children as $child)
                                <a href="{{ store_route('collections.show', ['slug' => $child->slug]) }}" class="mega-link" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 16px; line-height: 1.3; color: rgb(32, 32, 32); padding: 11px 0px; border-bottom: 1px solid rgb(244, 242, 238);">
                                    <span><span>{{ $child->name }}</span></span>
                                    <span class="mega-arrow" style="color: rgb(29, 29, 29); opacity: 0; transform: translateX(-6px); transition: opacity 250ms, transform 250ms;"><svg fill="none" height="16" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" width="16" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}" class="mega-feature" style="position: relative; aspect-ratio: 16 / 11; overflow: hidden; background: rgb(239, 236, 231); cursor: pointer; display: block;">
                        @if ($category->image)
                            <div class="mega-feature-img" style="position: absolute; inset: 0px; transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);"><img alt="{{ $category->name }}" src="{{ $category->image->url('large') }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;"></div>
                        @endif
                        <div style="position: absolute; inset: 0px; background: linear-gradient(rgba(15, 15, 15, 0) 40%, rgba(15, 15, 15, 0.6) 100%); pointer-events: none;"></div>
                        <div style="position: absolute; left: 0px; right: 0px; bottom: 0px; padding: 22px; display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; color: rgb(255, 255, 255); pointer-events: none;">
                            <div>
                                <div style="font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; opacity: 0.82; margin-bottom: 6px;">{{ __('store.theme.featured') }}</div>
                                <div style="font-size: 20px; font-weight: 600; letter-spacing: -0.01em;"><span>{{ $category->name }}</span></div>
                            </div>
                            <span style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, 0.7); display: flex; align-items: center; justify-content: center;"><svg fill="none" height="15" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" width="15" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span>
                        </div>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- The phone menu lives on the page body: the header's blur would otherwise clip it,
         because a backdrop-filter makes an element the containing block for fixed children. --}}
    <template x-teleport="body">
        <div>
    {{-- The phone menu. Categories, their sub-categories, and the links the icons drop. --}}
    <div x-show="menuOpen" x-cloak @click="closeMenu()" @keydown.escape.window="closeMenu()"
         style="position: fixed; inset: 0; background: rgba(20, 20, 20, 0.45); z-index: 60;"
         x-transition.opacity.duration.250ms></div>

    <div id="fv-mobile-menu" class="fv-mobile-menu" x-show="menuOpen" x-cloak
         @keydown.escape.window="closeMenu()"
         style="position: fixed; top: 0; bottom: 0; inset-inline-start: 0; width: min(86vw, 380px); background: rgb(255, 255, 255); color: rgb(32, 32, 32); z-index: 61; box-shadow: 0 0 60px rgba(0,0,0,.28);"
         x-transition:enter="fv-slide-enter" x-transition:enter-start="fv-slide-start" x-transition:leave="fv-slide-leave" x-transition:leave-end="fv-slide-start">

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 20px; border-bottom: 1px solid rgb(236, 236, 236);">
            <a href="{{ store_route('home') }}" style="display: block; width: 120px;" aria-label="{{ $storeName }}">
                <img src="{{ $logoDark }}" alt="{{ $storeName }}" style="width: 100%; height: auto; object-fit: contain; object-position: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
            </a>
            <button type="button" @click="closeMenu()" aria-label="{{ __('store.common.close') }}"
                    style="border: none; background: transparent; cursor: pointer; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
            </button>
        </div>

        <nav aria-label="{{ __('store.nav.shop') }}" style="flex: 1 1 auto; overflow-y: auto; padding: 8px 0 24px;">
            @foreach ($navCategories as $category)
                <div style="border-bottom: 1px solid rgb(244, 243, 240);">
                    <div style="display: flex; align-items: stretch;">
                        <a href="{{ store_route('collections.show', ['slug' => $category->slug]) }}"
                           style="flex: 1 1 auto; padding: 15px 20px; font-size: 14px; letter-spacing: 0.1em; text-transform: uppercase; text-decoration: none; color: inherit;">{{ $category->name }}</a>
                        @if ($category->children->isNotEmpty())
                            <button type="button" @click="openGroup = openGroup === {{ $category->id }} ? null : {{ $category->id }}"
                                    :aria-expanded="openGroup === {{ $category->id }}" aria-label="{{ $category->name }}"
                                    style="width: 52px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgb(139, 135, 127);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"
                                     :style="{ transform: openGroup === {{ $category->id }} ? 'rotate(180deg)' : 'none', transition: 'transform 220ms' }"><path d="M6 9l6 6 6-6"></path></svg>
                            </button>
                        @endif
                    </div>
                    @if ($category->children->isNotEmpty())
                        <div x-show="openGroup === {{ $category->id }}" x-collapse x-cloak style="padding: 0 20px 14px;">
                            @foreach ($category->children as $child)
                                <a href="{{ store_route('collections.show', ['slug' => $child->slug]) }}"
                                   style="display: block; padding: 10px 0; font-size: 15px; color: rgb(85, 85, 85); text-decoration: none;">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- What the icon row drops on a small screen. --}}
            <div style="padding: 18px 20px 0;">
                <a href="{{ store_route('collections.show', ['slug' => 'sale']) }}" style="display: block; padding: 10px 0; font-size: 15px; color: rgb(32, 32, 32); text-decoration: none;">{{ __('store.collections.sale') }}</a>
                <a href="{{ store_route('wishlist') }}" style="display: block; padding: 10px 0; font-size: 15px; color: rgb(32, 32, 32); text-decoration: none;">{{ __('store.nav.wishlist') }}</a>
                <a href="{{ auth('web')->check() ? store_route('account.dashboard') : store_route('login') }}" style="display: block; padding: 10px 0; font-size: 15px; color: rgb(32, 32, 32); text-decoration: none;">{{ auth('web')->check() ? __('store.account.title') : __('store.auth.login_title') }}</a>
                <a href="{{ store_route('pages.show', ['slug' => 'contact']) }}" style="display: block; padding: 10px 0; font-size: 15px; color: rgb(32, 32, 32); text-decoration: none;">{{ __('store.footer.help') }}</a>
            </div>
        </nav>

        <div style="padding: 16px 20px 22px; border-top: 1px solid rgb(236, 236, 236);">
            <a href="{{ store_route('custom-order') }}" @click.prevent="closeMenu(); $dispatch('open-custom-order')"
               style="display: flex; align-items: center; justify-content: center; height: 50px; background: rgb(29, 29, 29); color: rgb(255, 255, 255); font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; text-decoration: none;">{{ __('store.custom.title') }}</a>
        </div>
    </div>
        </div>
    </template>

</header>
