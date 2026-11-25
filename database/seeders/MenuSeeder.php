<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use App\Services\Navigation\MenuRepository;

/**
 * Starter navigation. Links point at pages that arrive in later phases
 * (collections, CMS pages); menus become editable in Admin → Content → Navigation.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'header' => ['Header', [
                [['en' => 'New in', 'ar' => 'وصل حديثاً'], '/collections/new-in'],
                [['en' => 'Living', 'ar' => 'غرفة المعيشة'], '/collections/living', [
                    [['en' => 'Cushions', 'ar' => 'وسائد'], '/collections/cushions'],
                    [['en' => 'Throws', 'ar' => 'أغطية'], '/collections/throws'],
                    [['en' => 'Candles & scents', 'ar' => 'شموع وعطور'], '/collections/candles'],
                ]],
                [['en' => 'Dining', 'ar' => 'السفرة'], '/collections/dining', [
                    [['en' => 'Serveware', 'ar' => 'أدوات التقديم'], '/collections/serveware'],
                    [['en' => 'Coffee & tea', 'ar' => 'القهوة والشاي'], '/collections/coffee-tea'],
                ]],
                [['en' => 'Gifts', 'ar' => 'هدايا'], '/collections/gifts'],
                [['en' => 'Sale', 'ar' => 'تخفيضات'], '/collections/sale'],
            ]],
            'footer-shop' => ['Footer — Shop', [
                [['en' => 'New in', 'ar' => 'وصل حديثاً'], '/collections/new-in'],
                [['en' => 'Living', 'ar' => 'غرفة المعيشة'], '/collections/living'],
                [['en' => 'Dining', 'ar' => 'السفرة'], '/collections/dining'],
                [['en' => 'Gifts', 'ar' => 'هدايا'], '/collections/gifts'],
            ]],
            'footer-help' => ['Footer — Help', [
                [['en' => 'Delivery & returns', 'ar' => 'التوصيل والإرجاع'], '/pages/delivery-returns'],
                [['en' => 'Contact us', 'ar' => 'تواصل معنا'], '/pages/contact'],
                [['en' => 'About us', 'ar' => 'من نحن'], '/pages/about'],
                [['en' => 'Privacy policy', 'ar' => 'سياسة الخصوصية'], '/pages/privacy'],
                [['en' => 'Terms & conditions', 'ar' => 'الشروط والأحكام'], '/pages/terms'],
            ]],
        ];

        foreach ($menus as $handle => [$name, $items]) {
            $menu = Menu::firstOrCreate(['handle' => $handle], ['name' => $name]);

            if ($menu->items()->exists()) {
                continue;
            }

            $this->createItems($menu, $items);
        }

        app(MenuRepository::class)->flush();
    }

    private function createItems(Menu $menu, array $items, ?int $parentId = null): void
    {
        foreach ($items as $position => $item) {
            [$label, $url] = $item;

            $created = $menu->items()->create([
                'parent_id' => $parentId,
                'label' => $label,
                'url' => $url,
                'sort_order' => $position,
            ]);

            if (! empty($item[2])) {
                $this->createItems($menu, $item[2], $created->id);
            }
        }
    }
}
