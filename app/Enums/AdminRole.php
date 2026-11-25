<?php

namespace App\Enums;

/**
 * What each kind of admin may open. Areas are matched to route names in AREAS below,
 * so a new admin page only needs its route name added there.
 */
enum AdminRole: string
{
    case Owner = 'owner';       // everything, including settings and other admins
    case Manager = 'manager';   // runs the shop day to day, but no settings or admin accounts
    case Orders = 'orders';     // orders, customers and stock
    case Content = 'content';

    /** The Super Admin is the store owner. */
    public const SUPER_ADMIN = self::Owner;   // products, pages, banners, menus and reviews

    /** Route name patterns → area. Pages not listed (dashboard, own account) are open to all admins. */
    public const AREAS = [
        'admin.orders.*' => 'orders',
        'admin.fulfillments.*' => 'orders',
        'admin.draft-orders.*' => 'orders',
        'admin.customers.*' => 'orders',
        'admin.inventory.*' => 'inventory',
        'admin.products.*' => 'catalog',
        'admin.categories.*' => 'catalog',
        'admin.media.*' => 'catalog',
        'admin.pages.*' => 'content',
        'admin.banners.*' => 'content',
        'admin.menus.*' => 'content',
        'admin.reviews.*' => 'reviews',
        'admin.discounts.*' => 'marketing',
        'admin.promotions.*' => 'marketing',
        'admin.returns.*' => 'orders',
        'admin.analytics' => 'analytics',
        'admin.traffic*' => 'analytics',
        'admin.activity' => 'activity',
        'admin.settings.*' => 'settings',
        // Custom code and the store password: Owner only. Themes: anyone who manages content.
        'admin.online-store.code*' => 'settings',
        'admin.online-store.preferences*' => 'settings',
        'admin.online-store.*' => 'content',
        'admin.admins.*' => 'admins',
        // Screens added after the first release; every admin route must appear here or in
        // OPEN_TO_ALL below, or it is refused (see AuthorizeAdminArea).
        'admin.brands.*' => 'catalog',
        'admin.enquiries.*' => 'orders',
        'admin.subscribers.*' => 'marketing',
        'admin.tax.*' => 'settings',
        'admin.shipping.*' => 'settings',
        'admin.tracking.*' => 'settings',
        'admin.search' => 'catalog',
    ];

    /**
     * Pages any signed-in admin may open: their own dashboard and account, the language
     * switch, and saved views. Everything else must be mapped in AREAS above — an admin
     * route that matches neither is refused, so a new screen cannot ship unprotected.
     *
     * @var list<string>
     */
    public const OPEN_TO_ALL = [
        'admin.dashboard',
        'admin.account.*',
        'admin.locale',
        'admin.logout',
        'admin.views.*',
        'admin.tours.*',
        'admin.login',
        'admin.login.store',
        'admin.password.*',
    ];

    /** True when the route is one every admin may open. */
    public static function isOpenToAll(?string $route): bool
    {
        if ($route === null) {
            return false;
        }

        foreach (self::OPEN_TO_ALL as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, $route)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function areas(): array
    {
        return match ($this) {
            self::Owner => array_values(array_unique(self::AREAS)),
            self::Manager => ['orders', 'inventory', 'catalog', 'content', 'reviews', 'marketing', 'analytics', 'activity'],
            self::Orders => ['orders', 'inventory'],
            self::Content => ['catalog', 'content', 'reviews'],
        };
    }

    public function allows(string $area): bool
    {
        return in_array($area, $this->areas(), true);
    }

    /** The area a route belongs to, or null if every admin may open it. */
    public static function areaForRoute(?string $routeName): ?string
    {
        foreach (self::AREAS as $pattern => $area) {
            if ($routeName !== null && \Illuminate\Support\Str::is($pattern, $routeName)) {
                return $area;
            }
        }

        return null;
    }

    public function label(): string
    {
        return __("admin.admins.roles.{$this->value}");
    }

    public function description(): string
    {
        return __("admin.admins.role_help.{$this->value}");
    }
}
