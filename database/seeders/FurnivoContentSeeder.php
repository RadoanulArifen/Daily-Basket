<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/** Furnivo's content pages, from the design's own text (furnivo/design-data.json). */
class FurnivoContentSeeder extends Seeder
{
    /** Design page id → URL handle. */
    public const PAGE_SLUGS = ['warranty' => 'warranty-policy', 'terms' => 'terms', 'privacy' => 'privacy', 'consumer' => 'consumer-rights', 'delivery' => 'delivery', 'returns' => 'delivery-returns'];

    /** The design's small print under every legal page. */
    public const FOOTNOTE = 'Last updated July 2026. For questions, contact hello@furnivo.com.';

    /** The design's About pictures (Unsplash ids), downloaded by store:import-design-images. */
    public const ABOUT_IMAGES = ['1618221195710-dd6b41faaea6', '1533090161767-e6ffed986c88', '1524758631624-e2822e304c36'];

    public function run(): void
    {
        // A fresh install: fills in what is empty and never overwrites the shop owner's words.
        $this->syncPages(overwrite: false);
        $this->syncProductDefaults(overwrite: false);
        $this->seedFooterMenus();
    }

    /**
     * Every page with the design's words: eyebrow, headline, introduction, sections and footnote.
     * With $overwrite (php artisan store:furnivo-pages --force) the design pages are reset to the
     * design; otherwise only empty fields are filled.
     */
    public function syncPages(bool $overwrite): int
    {
        $data = json_decode(file_get_contents(FurnivoSeeder::DATA), true);
        $h = fn (string $text) => e($text);
        $sections = fn (array $list) => collect($list)->map(fn ($s) => '<h2>'.$h($s['h']).'</h2><p>'.$h($s['b']).'</p>')->implode('');

        $pages = [];
        foreach ($data['PAGES'] as $id => $page) {
            $pages[self::PAGE_SLUGS[$id] ?? $id] = [
                'title' => $page['title'], 'eyebrow' => $page['eyebrow'] ?? null, 'intro' => $page['intro'] ?? null,
                'body' => $sections($page['sections'] ?? []), 'footnote' => $page['footnote'] ?? self::FOOTNOTE,
                'seo_description' => $page['intro'] ?? null,
            ];
        }
        $pages['faqs'] = [
            'title' => 'FAQs', 'eyebrow' => 'Help centre', 'heading' => 'Frequently asked questions',
            'body' => collect($data['FAQS'])->map(fn ($f) => '<h2>'.$h($f['q']).'</h2><p>'.$h($f['a']).'</p>')->implode(''),
        ];
        $pages['about'] = [
            'title' => 'About us', 'eyebrow' => 'Our Brand', 'heading' => "We don't settle for mediocre.",
            'body' => '<p>For decades, Furnivo has been driven by a single conviction: that furniture is at once function and art, and deserves to be treated as both.</p><p>We serve two kinds of client equally — those who want classical, luxury statement pieces, and those who want modern, minimalist design. We bring the world\'s finest ateliers under one roof, and where a piece must be made to measure, we build it to the client\'s exact specification.</p><p>Every dimension, material, finish and configuration is a choice. That is the point. We help discerning clients find one-of-a-kind pieces for a singular vision — and we do not do average.</p>',
        ];
        $pages['careers'] = [
            'title' => 'Careers', 'eyebrow' => 'Careers at Furnivo', 'heading' => 'Build the exceptional.',
            'intro' => 'We hire people who refuse to do average — designers, makers, and hosts who care about craft as much as we do. Explore open roles across our London studio, workshop, and showroom.',
            'body' => collect([
                ['Senior Furniture Designer', 'Design Studio · London · Full-time'],
                ['Master Upholsterer', 'Workshop · London · Full-time'],
                ['Showroom Host & Design Consultant', 'Showroom · Marylebone · Full-time'],
                ['Bespoke Project Manager', 'Operations · London · Full-time'],
                ['E-commerce & Digital Lead', 'Marketing · London · Full-time'],
            ])->map(fn ($r) => '<h2>'.$h($r[0]).'</h2><p>'.$h($r[1]).'</p>')->implode(''),
        ];
        $pages['contact'] = ['title' => 'Contact', 'eyebrow' => 'Contact', 'heading' => 'Ways to contact us', 'body' => ''];
        $pages['custom-order'] = ['title' => 'Custom Order', 'body' => ''];
        $pages['size-guide'] = [
            'title' => 'Size guide', 'eyebrow' => 'Service',
            'intro' => 'Typical dimensions for each kind of piece. Every piece can also be made to your own measurements — send us the dimensions through Custom Order and we will quote for them.',
            'body' => $sections([
                ['h' => 'Seating', 'b' => '2-seater 160 cm wide, 95 cm deep. 3-seater 220 × 95 cm. Armchair 80 × 85 cm. Sectional 300 × 170 cm.'],
                ['h' => 'Tables', 'b' => '4-seat 140 × 90 cm. 6-seat 200 × 100 cm. 8-seat 260 × 110 cm. Coffee table 120 × 60 cm.'],
                ['h' => 'Storage & cabinetry', 'b' => 'Compact 120 cm wide, 45 cm deep. Standard 180 × 45 cm. Large 240 × 50 cm, up to 200 cm tall.'],
                ['h' => 'Beds & bedroom', 'b' => 'Queen 160 × 200 cm. King 180 × 200 cm. Super king 200 × 200 cm. Nightstand 55 × 40 cm.'],
                ['h' => 'Lighting', 'b' => 'Pendant 40 cm across, 35 cm tall. Chandelier 90 × 110 cm. Floor lamp 45 × 160 cm. Wall light 25 × 30 cm.'],
            ]),
        ];

        $changed = 0;
        foreach ($pages as $slug => $fields) {
            $page = Page::query()->withoutGlobalScopes()->firstOrNew(['slug' => $slug]);
            if (! $page->exists) {
                $page->fill(['is_active' => true, 'show_contact_form' => in_array($slug, ['contact', 'custom-order'], true)]);
            }
            foreach ($fields as $field => $value) {
                if ($value === null) {
                    continue;
                }
                if ($overwrite || ! $page->exists || blank($page->getTranslation($field, 'en', false))) {
                    $page->setTranslation($field, 'en', $value);
                }
            }
            if ($page->isDirty()) {
                $page->save();
                $changed++;
            }
        }

        return $changed;
    }

    /** The product page's store-wide texts (Online Store → Preferences), from the design. */
    public function syncProductDefaults(bool $overwrite): void
    {
        $settings = app(\App\Services\Settings\SettingsService::class);
        $defaults = [
            'dimensions' => '<p>Made-to-order dimensions available — specify your exact size, finish and configuration via Custom Order.</p>',
            'delivery_returns' => '<p>In-stock pieces ship within 2 weeks; made-to-order 6–12 weeks. White-glove delivery and installation across the UK. 14-day returns on as-shown pieces.</p>',
        ];
        $current = $settings->group('product_page');
        $values = [];
        foreach ($defaults as $key => $html) {
            if ($overwrite || blank(($current[$key] ?? [])['en'] ?? null)) {
                $values[$key] = ['en' => $html] + (array) ($current[$key] ?? []);
                $values[$key]['en'] = $html;
            }
        }
        if ($values) {
            $settings->setMany('product_page', $values);
        }
    }

    /**
     * The footer's three link columns, so they can be changed in Content → Menus rather than
     * only in code. Existing menus are left alone.
     */
    private function seedFooterMenus(): void
    {
        $menus = [
            'footer-experience' => ['Experience', [
                ['View Your Order', '/account/orders'],
                ['Request a Return', '/account/orders'],
                ['Offers', '/collections/sale'],
            ]],
            'footer-discover' => ['Discover', [
                ['About us', '/pages/about'],
                ['Careers', '/pages/careers'],
                ['Warranty Policy', '/pages/warranty-policy'],
                ['Terms of Use', '/pages/terms'],
                ['Privacy Policy', '/pages/privacy'],
                ['Consumer Rights', '/pages/consumer-rights'],
            ]],
            'footer-support' => ['Support', [
                ['Contact Us', '/pages/contact'],
                ['Delivery', '/pages/delivery'],
                ['Return & Refund', '/pages/delivery-returns'],
                ['FAQs', '/pages/faqs'],
            ]],
        ];

        foreach ($menus as $handle => [$name, $items]) {
            $menu = \App\Models\Menu::query()->firstOrCreate(['handle' => $handle], ['name' => $name]);

            if ($menu->items()->exists()) {
                continue;   // the shop owner has their own links here
            }

            foreach ($items as $i => [$label, $url]) {
                $menu->items()->create(['label' => ['en' => $label], 'url' => $url, 'sort_order' => $i]);
            }
        }
    }
}
