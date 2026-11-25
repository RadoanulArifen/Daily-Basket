<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Demo store settings. Existing values are never overwritten,
 * so re-seeding won't undo changes made in the admin panel.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'general' => [
                'store_name' => ['en' => 'Demo Store', 'ar' => 'متجر تجريبي'],
                'tagline' => [
                    'en' => 'Homeware and gifts, woven with the Emirates in mind.',
                    'ar' => 'مستلزمات منزلية وهدايا مستوحاة من روح الإمارات.',
                ],
                'address' => [
                    'en' => "Warehouse 14, Al Quoz Industrial Area 3\nDubai, United Arab Emirates",
                    'ar' => "مستودع 14، القوز الصناعية 3\nدبي، الإمارات العربية المتحدة",
                ],
                'meta_description' => [
                    'en' => 'Shop cushions, throws, serveware and gifts with free delivery across the UAE on orders over AED 200.',
                    'ar' => 'تسوّق الوسائد والأغطية وأدوات التقديم والهدايا مع توصيل مجاني داخل الإمارات للطلبات فوق 200 درهم.',
                ],
                'announcement_enabled' => true,
                'announcement_text' => [
                    'en' => 'Free delivery across the UAE on orders over AED 200',
                    'ar' => 'توصيل مجاني داخل الإمارات للطلبات فوق 200 درهم',
                ],
                'store_email' => 'hello@example.com',
                'store_phone' => '+971 4 000 0000',
                'whatsapp' => '+971 50 000 0000',
                'trn' => null,
                'logo' => null,
                'instagram_url' => 'https://instagram.com/',
                'facebook_url' => null,
                'tiktok_url' => null,
                'x_url' => null,
            ],
            'tax' => [
                'enabled' => true,
                'rate' => '5.00',
                'label' => ['en' => 'VAT', 'ar' => 'ضريبة القيمة المضافة'],
                'prices_include_tax' => true,
                'show_on_invoice' => true,
            ],
            'shipping' => [
                'flat_enabled' => true,
                'flat_name' => ['en' => 'Standard delivery', 'ar' => 'التوصيل العادي'],
                'flat_description' => ['en' => '1–3 working days across the UAE', 'ar' => 'خلال 1–3 أيام عمل داخل الإمارات'],
                'flat_rate' => '25.00',
                'free_enabled' => true,
                'free_threshold' => '200.00',
            ],
            'payments' => [
                'cod_enabled' => true,
                'cod_fee' => '0.00',
                'stripe_enabled' => false,
            ],
            'checkout' => [
                'guest_checkout' => true,
                'require_phone' => true,
                'order_notes' => true,
                'marketing_opt_in' => true,
            ],
        ];

        foreach ($defaults as $group => $values) {
            foreach ($values as $key => $value) {
                Setting::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }

        app(SettingsService::class)->flush();
    }
}
