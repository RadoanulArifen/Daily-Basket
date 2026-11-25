<?php

namespace App\Services\Navigation;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront menus (header, footer…), loaded once and cached.
 *
 * The cache holds plain arrays — Laravel does not unserialize objects from
 * the cache — and models are rebuilt from them on each request.
 */
class MenuRepository
{
    private const CACHE_KEY = 'store.menus';

    private ?Collection $menus = null;

    /** @return Collection<string, Menu> keyed by handle, each with ->tree (active top-level items + ->children) */
    public function all(): Collection
    {
        return $this->menus ??= $this->build(Cache::rememberForever(self::CACHE_KEY, fn () => [
            'menus' => Menu::query()->get()->map->getAttributes()->all(),
            'items' => MenuItem::query()->where('is_active', true)->orderBy('sort_order')->get()->map->getAttributes()->all(),
        ]));
    }

    public function get(string $handle): ?Menu
    {
        return $this->all()->get($handle);
    }

    public function flush(): void
    {
        $this->menus = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function build(array $cached): Collection
    {
        $items = MenuItem::hydrate($cached['items']);
        $children = $items->whereNotNull('parent_id')->groupBy('parent_id');

        $items->each(fn (MenuItem $item) => $item->setRelation('children', $children->get($item->id, collect())->values()));

        return Menu::hydrate($cached['menus'])
            ->each(fn (Menu $menu) => $menu->setRelation(
                'tree',
                $items->where('menu_id', $menu->id)->whereNull('parent_id')->values(),
            ))
            ->keyBy('handle');
    }
}
