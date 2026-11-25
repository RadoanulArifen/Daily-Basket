<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Arabic names for the demo categories.
 *
 * The design's own content is written in English. This fills in Arabic for the navigation,
 * which is what a visitor sees first when they switch language. Product copy stays English
 * until the shop owner translates it in the admin — every field has an Arabic tab.
 *
 * Categories that already have an Arabic name are left alone.
 */
class FurnivoArabicSeeder extends Seeder
{
    /** English name => Arabic name. */
    private const NAMES = [
        'Seating' => 'الجلوس',
        'Sofas & Sectionals' => 'الأرائك والزوايا',
        'Armchairs & Lounge Chairs' => 'الكراسي والمقاعد المريحة',
        'Dining Chairs' => 'كراسي الطعام',
        'Ottomans & Benches' => 'المساند والمقاعد الطويلة',
        'Stools' => 'الكراسي العالية',
        'Tables' => 'الطاولات',
        'Dining Tables' => 'طاولات الطعام',
        'Coffee Tables' => 'طاولات القهوة',
        'Side & Console Tables' => 'الطاولات الجانبية والكونسول',
        'Desks' => 'المكاتب',
        'Storage & Cabinetry' => 'التخزين والخزائن',
        'Sideboards & Buffets' => 'البوفيهات والخزائن الجانبية',
        'Bookcases & Shelving' => 'المكتبات والأرفف',
        'Media Units' => 'وحدات التلفزيون',
        'Cabinets' => 'الخزائن',
        'Beds & Bedroom' => 'غرف النوم',
        'Beds' => 'الأسرّة',
        'Nightstands' => 'الطاولات الجانبية للسرير',
        'Dressers & Chests' => 'خزائن الملابس والأدراج',
        'Wardrobes' => 'الدواليب',
        'Lighting' => 'الإضاءة',
        'Pendants' => 'المعلقات',
        'Chandeliers' => 'الثريات',
        'Floor Lamps' => 'مصابيح أرضية',
        'Table Lamps' => 'مصابيح طاولة',
        'Wall Lights' => 'إضاءة الجدران',
        'New In' => 'وصل حديثاً',
        'Sale' => 'العروض',
        'Offers' => 'العروض',
    ];

    public function run(): void
    {
        $filled = 0;

        foreach (Category::query()->get() as $category) {
            if (filled($category->getTranslation('name', 'ar', false))) {
                continue;
            }

            $english = $category->getTranslation('name', 'en', false);

            if ($arabic = self::NAMES[$english] ?? null) {
                $category->setTranslation('name', 'ar', $arabic)->save();
                $filled++;
            }
        }

        $this->command?->info("Arabic names added to {$filled} categor(ies).");
    }
}
