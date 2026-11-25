@php
    // Items without a route arrive in later phases and show a "Soon" label.
    $navigation = [
        ['label' => __('admin.nav.home'), 'icon' => 'house', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        [
            'label' => __('admin.nav.orders'), 'icon' => 'shopping-bag', 'route' => 'admin.orders.index', 'active' => ['admin.orders.*', 'admin.returns.*', 'admin.enquiries.*', 'admin.draft-orders.*'],
            'children' => [
                ['label' => __('admin.nav.all_orders'), 'route' => 'admin.orders.index', 'active' => 'admin.orders.*'],
                ['label' => __('admin.nav.returns'), 'route' => 'admin.returns.index', 'active' => 'admin.returns.*'],
                ['label' => __('admin.nav.drafts'), 'route' => 'admin.draft-orders.drafts', 'active' => 'admin.draft-orders.*'],
                ['label' => __('admin.nav.enquiries'), 'route' => 'admin.enquiries.index', 'active' => 'admin.enquiries.*'],
            ],
        ],
        [
            'label' => __('admin.nav.products'), 'icon' => 'package', 'route' => 'admin.products.index',
            'active' => ['admin.products.*', 'admin.categories.*', 'admin.brands.*', 'admin.inventory.*'],
            'children' => [
                ['label' => __('admin.nav.all_products'), 'route' => 'admin.products.index', 'active' => 'admin.products.*'],
                ['label' => __('admin.nav.categories'), 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
                ['label' => __('admin.nav.brands'), 'route' => 'admin.brands.index', 'active' => 'admin.brands.*'],
                ['label' => __('admin.nav.inventory'), 'route' => 'admin.inventory.index', 'active' => 'admin.inventory.*'],
            ],
        ],
        ['label' => __('admin.nav.media'), 'icon' => 'image', 'route' => 'admin.media.index', 'active' => 'admin.media.*'],
        ['label' => __('admin.nav.customers'), 'icon' => 'users', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*'],
        [
            'label' => __('admin.nav.marketing'), 'icon' => 'badge-percent', 'route' => 'admin.discounts.index',
            'active' => ['admin.discounts.*', 'admin.promotions.*', 'admin.reviews.*', 'admin.subscribers.*'],
            'children' => [
                ['label' => __('admin.nav.discounts'), 'route' => 'admin.discounts.index', 'active' => 'admin.discounts.*'],
                ['label' => __('admin.nav.deals'), 'route' => 'admin.promotions.index', 'active' => 'admin.promotions.*'],
                ['label' => __('admin.nav.reviews'), 'route' => 'admin.reviews.index', 'active' => 'admin.reviews.*'],
                ['label' => __('admin.nav.subscribers'), 'route' => 'admin.subscribers.index', 'active' => 'admin.subscribers.*'],
            ],
        ],
        [
            'label' => __('admin.nav.content'), 'icon' => 'layout-template', 'route' => 'admin.pages.index',
            'active' => ['admin.pages.*', 'admin.banners.*', 'admin.menus.*'],
            'children' => [
                ['label' => __('admin.nav.pages'), 'route' => 'admin.pages.index', 'active' => 'admin.pages.*'],
                ['label' => __('admin.nav.banners'), 'route' => 'admin.banners.index', 'active' => 'admin.banners.*'],
                ['label' => __('admin.nav.menus'), 'route' => 'admin.menus.index', 'active' => 'admin.menus.*'],
            ],
        ],
        ['label' => __('admin.nav.analytics'), 'icon' => 'chart-column', 'route' => 'admin.analytics', 'active' => 'admin.analytics'],
    ];

    // Show only what this admin's role may open (App\Enums\AdminRole).
    $me = auth('admin')->user();
    $allowed = fn (?string $route) => ! $route || ! ($area = \App\Enums\AdminRole::areaForRoute($route)) || $me?->mayAccess($area);
    $navigation = collect($navigation)
        ->map(function ($item) use ($allowed) {
            if (! empty($item['children'])) {
                $item['children'] = array_values(array_filter($item['children'], fn ($c) => $allowed($c['route'])));
                if ($item['children'] && ! $allowed($item['route'] ?? null)) {
                    $item['route'] = $item['children'][0]['route'];
                }
                if (! $item['children']) {
                    return null;
                }
            }

            return $allowed($item['route'] ?? null) ? $item : null;
        })->filter()->values()->all();
@endphp

<aside :class="sidebarOpen ? 'max-lg:flex! max-lg:shadow-popover' : ''"
       class="fixed inset-y-0 start-0 top-14 z-30 flex w-60 flex-col bg-sidebar max-lg:hidden"
       aria-label="{{ __('admin.nav.label') }}">
    <nav class="flex flex-1 flex-col overflow-y-auto px-2 py-3">
        <ul class="space-y-px">
            @foreach ($navigation as $item)
                @php
                    $sectionActive = request()->routeIs(...(array) $item['active']);
                @endphp
                <li>
                    <x-admin.nav-link :href="route($item['route'])" :icon="$item['icon']" :active="$sectionActive" :parent="! empty($item['children'])">{{ $item['label'] }}</x-admin.nav-link>

                    @if (! empty($item['children']) && $sectionActive)
                        <ul class="relative my-0.5 space-y-px">
                            @foreach ($item['children'] as $child)
                                @php $childActive = request()->routeIs($child['active']); @endphp
                                <li>
                                    <a href="{{ route($child['route']) }}" @if ($childActive) aria-current="page" @endif
                                       @class([
                                           'block rounded-lg py-1 ps-9 pe-2 text-[13px] transition',
                                           'bg-brand-50 font-semibold text-brand-600 shadow-[0_1px_0_rgba(0,0,0,.05)]' => $childActive,
                                           'text-ink-soft hover:bg-sidebar-hover' => ! $childActive,
                                       ])>{{ $child['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        <p class="mt-5 mb-1 px-2 text-xs font-medium text-muted">{{ __('admin.nav.sales_channels') }}</p>
        @php
            $storeActive = request()->routeIs('admin.online-store.*');
            $storeChildren = collect([
                ['label' => __('admin.online.themes'), 'route' => 'admin.online-store.themes', 'active' => ['admin.online-store.themes', 'admin.online-store.customize*']],
                ['label' => __('admin.online.code_title'), 'route' => 'admin.online-store.code', 'active' => ['admin.online-store.code*']],
                ['label' => __('admin.online.preferences'), 'route' => 'admin.online-store.preferences', 'active' => ['admin.online-store.preferences*']],
            ])->filter(fn ($c) => $allowed($c['route']))->values();
        @endphp
        <ul>
            <li>
                <div class="flex items-center gap-0.5">
                    @if ($storeChildren->isNotEmpty())
                        <div class="min-w-0 flex-1"><x-admin.nav-link :href="route($storeChildren[0]['route'])" icon="store" :active="$storeActive" :parent="true">{{ __('admin.nav.online_store') }}</x-admin.nav-link></div>
                    @else
                        <span class="flex h-8 flex-1 items-center gap-2.5 px-2 text-[13px] font-medium text-ink-soft"><x-icon name="store" class="size-4" />{{ __('admin.nav.online_store') }}</span>
                    @endif
                    <a href="{{ store_url('/', config('store.default_locale')) }}" target="_blank" rel="noopener" class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-sidebar-hover hover:text-ink-strong" title="{{ __('admin.nav.view_store') }}" aria-label="{{ __('admin.nav.view_store') }}">
                        <x-icon name="external-link" class="size-3.5" />
                    </a>
                </div>
                @if ($storeActive)
                    <ul class="my-0.5 space-y-px">
                        @foreach ($storeChildren as $child)
                            @php $childActive = request()->routeIs(...$child['active']); @endphp
                            <li>
                                <a href="{{ route($child['route']) }}" @if ($childActive) aria-current="page" @endif
                                   @class(['block rounded-lg py-1 ps-9 pe-2 text-[13px] transition', 'bg-brand-50 font-semibold text-brand-600 shadow-[0_1px_0_rgba(0,0,0,.05)]' => $childActive, 'text-ink-soft hover:bg-sidebar-hover' => ! $childActive])>{{ $child['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        </ul>

        <ul class="mt-auto pt-4">
            @if ($me?->mayAccess('settings'))
                <li>
                    <x-admin.nav-link :href="route('admin.settings.index')" icon="settings"
                        :active="request()->routeIs('admin.settings.*') || request()->routeIs('admin.account.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.activity')">
                        {{ __('admin.nav.settings') }}
                    </x-admin.nav-link>
                </li>
            @else
                <li>
                    <x-admin.nav-link :href="route('admin.account.edit')" icon="user-cog" :active="request()->routeIs('admin.account.*')">
                        {{ __('admin.account.title') }}
                    </x-admin.nav-link>
                </li>
            @endif
        </ul>
    </nav>
</aside>
