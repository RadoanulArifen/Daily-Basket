@php
    $headerMenu = $storeMenus->get('header')?->tree ?? collect();
    $announcement = settings()->bool('general.announcement_enabled') ? settings()->trans('general.announcement_text') : null;
    $customer = auth('web')->user();
@endphp

<div class="weave-band" aria-hidden="true"></div>

{{-- Top bar: announcement + language/currency (desktop) --}}
<div class="bg-ink text-paper">
    <div class="mx-auto flex max-w-shop items-center justify-center gap-6 px-4 py-2 text-[13px] sm:px-6 lg:justify-between lg:px-10">
        <p class="text-center">{{ $announcement }}</p>
        <x-store.locale-currency :currencies="$storeCurrencies" :current="$currentCurrency" tone="dark" class="hidden lg:flex" />
    </div>
</div>

<header x-data="{ menuOpen: false, searchOpen: false }" @keydown.escape.window="menuOpen = false" class="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-shop items-center gap-4 px-4 sm:px-6 lg:h-20 lg:px-10">
        <button type="button" @click="menuOpen = true" class="-ms-2 p-2 lg:hidden" aria-label="{{ __('store.nav.open_menu') }}" :aria-expanded="menuOpen">
            <x-icon name="menu" class="size-6" />
        </button>

        <x-store.logo class="shrink-0" />

        {{-- Desktop navigation --}}
        <nav class="hidden flex-1 justify-center lg:flex" aria-label="{{ __('store.nav.main') }}">
            <ul class="flex items-center gap-1">
                @foreach ($headerMenu as $item)
                    <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @focusin="open = true" @focusout="open = $el.contains($event.relatedTarget)">
                        <a href="{{ $item->href() }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
                           class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-[15px] text-ink-soft transition hover:text-ink"
                           @if ($item->children->isNotEmpty()) aria-haspopup="true" :aria-expanded="open" @endif>
                            {{ $item->label }}
                            @if ($item->children->isNotEmpty())<x-icon name="chevron-down" class="size-4 text-stone" />@endif
                        </a>
                        @if ($item->children->isNotEmpty())
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute start-0 top-full pt-2">
                                <ul class="min-w-52 rounded-md border border-line bg-paper py-2 shadow-lg">
                                    @foreach ($item->children as $child)
                                        <li><a href="{{ $child->href() }}" class="block px-4 py-2 text-[15px] text-ink-soft hover:bg-wool-soft hover:text-ink">{{ $child->label }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Icons --}}
        <div class="ms-auto flex items-center gap-1 lg:ms-0">
            <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())" class="p-2 text-ink-soft hover:text-ink" aria-label="{{ __('store.nav.search') }}" :aria-expanded="searchOpen" aria-controls="header-search">
                <x-icon name="search" class="size-[22px]" />
            </button>
            <a href="{{ $customer ? store_route('account.dashboard') : store_route('login') }}" class="hidden p-2 text-ink-soft hover:text-ink sm:inline-flex" aria-label="{{ __('store.nav.account') }}">
                <x-icon name="user" class="size-[22px]" />
            </a>
            <a href="{{ store_route('wishlist') }}" class="relative hidden p-2 text-ink-soft hover:text-ink sm:inline-flex"
               x-data="{ count: {{ $wishlistCount }} }" @wishlist-updated.window="count = $event.detail.count"
               aria-label="{{ __('store.nav.wishlist') }}">
                <x-icon name="heart" class="size-[22px]" />
                <span x-show="count > 0" x-text="count" @if (! $wishlistCount) x-cloak @endif class="absolute end-0.5 top-0.5 flex min-w-4 items-center justify-center rounded-full bg-ink px-1 text-[10px] font-semibold text-paper">{{ $wishlistCount }}</span>
            </a>
            <a href="{{ store_route('cart') }}" class="relative p-2 text-ink-soft hover:text-ink"
               x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count"
               :aria-label="@js(__('store.nav.cart', ['count' => '__COUNT__'])).replace('__COUNT__', count)"
               aria-label="{{ __('store.nav.cart', ['count' => $cartCount]) }}">
                <x-icon name="shopping-bag" class="size-[22px]" />
                <span x-show="count > 0" x-text="count" @if (! $cartCount) x-cloak @endif class="absolute end-0.5 top-0.5 flex min-w-4 items-center justify-center rounded-full bg-brand-red px-1 text-[10px] font-semibold text-white">{{ $cartCount }}</span>
            </a>
        </div>
    </div>

    {{-- Search panel --}}
    <div id="header-search" x-show="searchOpen" x-cloak x-transition.opacity.duration.150ms @click.outside="searchOpen = false" @keydown.escape.window="searchOpen = false"
         class="absolute inset-x-0 top-full border-b border-line bg-paper shadow-sm">
        <form method="GET" action="{{ store_route('search') }}" role="search" data-tour="store-search" class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-4 sm:px-6">
            <x-icon name="search" class="size-5 shrink-0 text-stone" />
            <label for="header-search-q" class="sr-only">{{ __('store.search.label') }}</label>
            <input id="header-search-q" x-ref="searchInput" type="search" name="q" placeholder="{{ __('store.search.placeholder') }}" autocomplete="off"
                   class="min-w-0 flex-1 border-0 bg-transparent py-2 text-lg focus:outline-none">
            <button type="button" @click="searchOpen = false" class="p-2 text-ink-soft hover:text-ink" aria-label="{{ __('store.common.close') }}"><x-icon name="x" class="size-5" /></button>
        </form>
    </div>

    {{-- Mobile menu drawer --}}
    <div x-show="menuOpen" x-cloak class="lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('store.nav.menu') }}">
        <div x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="fixed inset-0 z-50 bg-ink/40"></div>
        <div x-show="menuOpen" x-trap.noscroll="menuOpen"
             x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full rtl:translate-x-full"
             class="fixed inset-y-0 start-0 z-50 flex w-[85vw] max-w-sm flex-col bg-paper">
            <div class="flex h-16 items-center justify-between border-b border-line px-4">
                <x-store.logo />
                <button type="button" @click="menuOpen = false" class="-me-2 p-2" aria-label="{{ __('store.nav.close_menu') }}">
                    <x-icon name="x" class="size-6" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="{{ __('store.nav.main') }}">
                <ul class="divide-y divide-line">
                    @foreach ($headerMenu as $item)
                        <li x-data="{ open: false }">
                            <div class="flex items-center">
                                <a href="{{ $item->href() }}" class="flex-1 py-3.5 text-base">{{ $item->label }}</a>
                                @if ($item->children->isNotEmpty())
                                    <button type="button" @click="open = ! open" :aria-expanded="open" class="p-3" aria-label="{{ __('store.nav.show_more', ['name' => $item->label]) }}">
                                        <x-icon name="chevron-down" class="size-5 transition" x-bind:class="open && 'rotate-180'" />
                                    </button>
                                @endif
                            </div>
                            @if ($item->children->isNotEmpty())
                                <ul x-show="open" x-cloak class="pb-3 ps-4">
                                    @foreach ($item->children as $child)
                                        <li><a href="{{ $child->href() }}" class="block py-2 text-[15px] text-ink-soft">{{ $child->label }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="space-y-4 border-t border-line p-4 text-sm">
                <a href="{{ $customer ? store_route('account.dashboard') : store_route('login') }}" class="flex items-center gap-2">
                    <x-icon name="user" class="size-5" />
                    {{ $customer ? __('store.nav.my_account') : __('store.auth.login') }}
                </a>
                <a href="{{ store_route('wishlist') }}" class="flex items-center gap-2">
                    <x-icon name="heart" class="size-5" />
                    {{ __('store.nav.wishlist') }}@if ($wishlistCount) ({{ $wishlistCount }})@endif
                </a>
                <x-store.locale-currency :currencies="$storeCurrencies" :current="$currentCurrency" tone="light" />
            </div>
        </div>
    </div>
</header>
