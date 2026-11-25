<?php

namespace App\Services\Tours;

use App\Enums\ProductStatus;
use App\Models\Product;

/**
 * The shopper tour on the storefront: search, categories, a product's sizes and Add to cart, then
 * checkout. Texts are in lang/{en,ar}/tours.php under store.*. Each theme marks the elements with
 * data-tour="store-…"; a theme that lacks one shows that step in the middle of the screen.
 *
 * Progress lives in the shopper's browser (resources/js/storefront/tour.js), so nothing is stored
 * on the server. Online Store → Preferences can switch the tour off.
 */
final class StorefrontTour
{
    /** @var list<array{key: string, page: string, tour: string}> */
    private const STEPS = [
        ['key' => 'search', 'page' => 'home', 'tour' => 'store-search'],
        ['key' => 'categories', 'page' => 'home', 'tour' => 'store-categories'],
        ['key' => 'options', 'page' => 'products.show', 'tour' => 'store-options'],
        ['key' => 'add', 'page' => 'products.show', 'tour' => 'store-add'],
        ['key' => 'checkout', 'page' => 'cart', 'tour' => 'store-checkout'],
    ];

    public static function enabled(): bool
    {
        return settings()->bool('preferences.storefront_tour', true);
    }

    /** The current storefront page's route name without the language prefix ("ar.cart" → "cart"). */
    public static function currentPage(): ?string
    {
        $name = request()->route()?->getName();
        if ($name === null) {
            return null;
        }
        $prefix = app()->getLocale().'.';

        return str_starts_with($name, $prefix) ? substr($name, strlen($prefix)) : $name;
    }

    /** @return array<string, mixed>|null everything the browser needs, or null when switched off */
    public static function payload(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $product = Product::query()->where('status', ProductStatus::Active)->orderByDesc('is_featured')->orderBy('id')->value('slug');

        $steps = array_values(array_filter(array_map(function (array $step) use ($product) {
            $url = match ($step['page']) {
                'products.show' => $product ? store_route('products.show', ['slug' => $product]) : null,
                default => store_route($step['page']),
            };

            return $url === null ? null : [
                'title' => __("tours.store.steps.{$step['key']}.title"),
                'text' => __("tours.store.steps.{$step['key']}.text"),
                'page' => $step['page'],
                'url' => $url,
                'target' => '[data-tour="'.$step['tour'].'"]',
            ];
        }, self::STEPS)));

        return [
            'route' => self::currentPage(),
            'steps' => $steps,
            'labels' => [
                'launcher' => __('tours.store.launcher'),
                'name' => __('tours.store.label'),
                'next' => __('tours.store.next'),
                'back' => __('tours.store.back'),
                'finish' => __('tours.store.finish'),
                'skip' => __('tours.store.skip'),
                'close' => __('tours.store.close'),
                'stepOf' => __('tours.store.step_of', ['step' => ':step', 'total' => ':total']),
            ],
        ];
    }
}
