<?php

namespace App\Services\Tours;

use App\Models\Order;

/**
 * The admin's guided tours: which steps each one has, the page each step happens on, and what it
 * points at. Texts live in lang/{en,ar}/tours.php under admin.<tour>.steps.<step>.
 *
 * A step's `page` is the admin route it runs on (null = any page, e.g. a sidebar link). Its
 * target is either `link` (the first visible <a> to that route) or `tour` (an element with that
 * data-tour attribute). When a target is not on screen the popup shows in the middle instead.
 */
final class AdminTourCatalog
{
    /** @return array<string, array{area: string, minutes: int, needs?: string, steps: list<array{key: string, page: ?string, link?: string, tour?: string, click?: bool}>}> */
    public static function definitions(): array
    {
        return [
            'product' => ['area' => 'catalog', 'minutes' => 3, 'steps' => [
                ['key' => 'menu', 'page' => null, 'link' => 'admin.products.index'],
                ['key' => 'add', 'page' => 'admin.products.index', 'link' => 'admin.products.create'],
                ['key' => 'details', 'page' => 'admin.products.create', 'tour' => 'product-details'],
                ['key' => 'media', 'page' => 'admin.products.create', 'tour' => 'product-media'],
                ['key' => 'options', 'page' => 'admin.products.create', 'tour' => 'product-options'],
                ['key' => 'status', 'page' => 'admin.products.create', 'tour' => 'product-status'],
            ]],
            'order' => ['area' => 'orders', 'minutes' => 4, 'needs' => 'order', 'steps' => [
                ['key' => 'menu', 'page' => null, 'link' => 'admin.orders.index'],
                ['key' => 'row', 'page' => 'admin.orders.index', 'tour' => 'order-row', 'click' => true],
                ['key' => 'payment', 'page' => 'admin.orders.show', 'tour' => 'order-payment'],
                ['key' => 'fulfill', 'page' => 'admin.orders.show', 'tour' => 'order-fulfill'],
                ['key' => 'timeline', 'page' => 'admin.orders.show', 'tour' => 'order-timeline'],
                ['key' => 'more', 'page' => 'admin.orders.show', 'tour' => 'order-more'],
                ['key' => 'customer', 'page' => 'admin.orders.show', 'tour' => 'order-customer'],
            ]],
            'payments' => ['area' => 'settings', 'minutes' => 3, 'steps' => [
                ['key' => 'menu', 'page' => null, 'link' => 'admin.settings.index'],
                ['key' => 'card', 'page' => 'admin.settings.index', 'link' => 'admin.settings.payments.edit'],
                ['key' => 'cod', 'page' => 'admin.settings.payments.edit', 'tour' => 'payments-cod'],
                ['key' => 'stripe', 'page' => 'admin.settings.payments.edit', 'tour' => 'payments-stripe'],
                ['key' => 'keys', 'page' => 'admin.settings.payments.edit', 'tour' => 'payments-keys'],
                ['key' => 'rates', 'page' => 'admin.settings.shipping.edit', 'tour' => 'shipping-rates'],
                ['key' => 'free', 'page' => 'admin.settings.shipping.edit', 'tour' => 'shipping-free'],
                ['key' => 'countries', 'page' => 'admin.settings.shipping.edit', 'tour' => 'shipping-countries'],
            ]],
            'storefront' => ['area' => 'content', 'minutes' => 3, 'steps' => [
                ['key' => 'menu', 'page' => null, 'link' => 'admin.online-store.themes'],
                ['key' => 'customize', 'page' => 'admin.online-store.themes', 'link' => 'admin.online-store.customize'],
                ['key' => 'sections', 'page' => 'admin.online-store.customize', 'tour' => 'customize-sections'],
                ['key' => 'preview', 'page' => 'admin.online-store.customize', 'tour' => 'customize-preview'],
                ['key' => 'publish', 'page' => 'admin.online-store.customize', 'tour' => 'customize-publish'],
                ['key' => 'banners', 'page' => 'admin.banners.index', 'link' => 'admin.banners.create'],
                ['key' => 'menus', 'page' => 'admin.menus.index', 'tour' => 'menus-list'],
            ]],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function has(?string $tour): bool
    {
        return is_string($tour) && array_key_exists($tour, self::definitions());
    }

    public static function stepCount(string $tour): int
    {
        return count(self::definitions()[$tour]['steps'] ?? []);
    }

    /** False when the tour needs data the shop does not have yet (the order tour needs an order). */
    public static function isAvailable(string $tour): bool
    {
        return match (self::definitions()[$tour]['needs'] ?? null) {
            'order' => Order::query()->exists(),
            default => true,
        };
    }

    /**
     * The steps as the browser needs them: text in the admin's language, the page (route name)
     * each runs on, the address to open for it and a CSS selector for what it points at.
     *
     * @return list<array{title: string, text: string, page: ?string, url: ?string, target: ?string, click: bool}>
     */
    public static function steps(string $tour): array
    {
        $definition = self::definitions()[$tour];
        $order = ($definition['needs'] ?? null) === 'order' ? Order::query()->latest('id')->first() : null;

        return array_map(function (array $step) use ($tour, $order) {
            $url = $step['page'] === null ? null : self::url($step['page'], $order);

            return [
                'title' => __("tours.admin.{$tour}.steps.{$step['key']}.title"),
                'text' => __("tours.admin.{$tour}.steps.{$step['key']}.text"),
                'page' => $step['page'],
                'url' => $url,
                // Clicking the target opens the next step's page (a link or an order row).
                'click' => isset($step['link']) || ($step['click'] ?? false),
                'target' => isset($step['link'])
                    ? 'a[href="'.self::url($step['link'], $order).'"]'
                    : (isset($step['tour']) ? '[data-tour="'.$step['tour'].'"]' : null),
            ];
        }, $definition['steps']);
    }

    private static function url(string $route, ?Order $order): string
    {
        return $route === 'admin.orders.show' && $order ? route($route, $order) : route($route);
    }
}
