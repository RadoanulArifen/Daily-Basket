@php
    $localization = app(\App\Support\Localization::class);
    $otherLocale = collect($localization->codes())->first(fn ($code) => $code !== app()->getLocale());
@endphp

{{-- Dark top bar: store, search (⌘K / Ctrl K), language and account. --}}
<header class="fixed inset-x-0 top-0 z-40 flex h-14 items-center gap-2 bg-topbar px-2 text-white sm:px-3">
    <button type="button" @click="sidebarOpen = ! sidebarOpen" class="rounded-lg p-2 text-white/80 hover:bg-white/10 lg:hidden" aria-label="{{ __('admin.common.open_menu') }}">
        <x-icon name="menu" class="size-5" />
    </button>

    <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-white/10 lg:w-56">
        @php $logoFile = settings()->get('general.logo_white') ?: settings()->get('general.logo'); @endphp
        @if ($logoFile)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($logoFile) }}" alt="{{ $storeName }}" style="height: 26px; width: auto; max-width: 140px; object-fit: contain; object-position: center; display: block;">
        @else
            <span class="flex size-7 items-center justify-center rounded-lg bg-warning-100 text-[13px] font-bold text-ink-strong">{{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}</span>
        @endif
        @unless ($logoFile)
            <span class="hidden truncate text-sm font-semibold sm:inline">{{ $storeName }}</span>
        @endunless
    </a>

    <div class="flex flex-1 justify-center">
        <button type="button" @click="$dispatch('open-search')"
                class="flex h-9 w-full max-w-[30rem] items-center gap-2 rounded-lg border border-white/10 bg-topbar-field px-3 text-start text-sm text-white/60 transition hover:border-white/20 hover:bg-brand-700">
            <x-icon name="search" class="size-4 shrink-0" />
            <span class="flex-1 truncate">{{ __('admin.search.placeholder') }}</span>
            <span class="hidden items-center gap-1 sm:flex" aria-hidden="true"><kbd class="admin-kbd" x-text="navigator.platform.includes('Mac') ? '⌘' : 'Ctrl'">Ctrl</kbd><kbd class="admin-kbd">K</kbd></span>
        </button>
    </div>

    <div class="flex shrink-0 items-center gap-1">
        @include('admin.partials.help-menu')

        @if ($otherLocale)
            <form method="POST" action="{{ route('admin.locale') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $otherLocale }}">
                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-sm text-white/80 hover:bg-white/10 hover:text-white" lang="{{ $otherLocale }}" title="{{ $localization->nativeName($otherLocale) }}">
                    <x-icon name="languages" class="size-4" />
                    <span class="hidden xl:inline">{{ $localization->nativeName($otherLocale) }}</span>
                </button>
            </form>
        @endif

        <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
            <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="menu" class="flex items-center gap-2 rounded-lg p-1 pe-2 hover:bg-white/10">
                <span class="flex size-7 items-center justify-center rounded-lg bg-[#b4c7ff] text-xs font-semibold text-ink-strong">{{ $admin->initials() }}</span>
                <span class="hidden max-w-36 truncate text-[13px] font-medium md:inline">{{ $admin->name }}</span>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top @click.outside="open = false" role="menu"
                 class="absolute end-0 mt-2 w-64 overflow-hidden rounded-xl bg-surface py-1.5 text-ink shadow-popover">
                <div class="px-3 py-2">
                    <p class="truncate text-sm font-semibold">{{ $admin->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $admin->email }} · {{ $admin->role->label() }}</p>
                </div>
                <div class="my-1 border-t border-line"></div>
                <a href="{{ route('admin.account.edit') }}" role="menuitem" class="mx-1.5 flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-subdued">
                    <x-icon name="user-cog" class="size-4 text-muted" /> {{ __('admin.account.title') }}
                </a>
                <a href="{{ store_url('/', config('store.default_locale')) }}" target="_blank" rel="noopener" role="menuitem" class="mx-1.5 flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-subdued">
                    <x-icon name="store" class="size-4 text-muted" /> {{ __('admin.nav.view_store') }}
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" role="menuitem" class="mx-1.5 flex w-[calc(100%-0.75rem)] items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-subdued">
                        <x-icon name="log-out" class="size-4 text-muted rtl:rotate-180" /> {{ __('admin.auth.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
