<?php

namespace Database\Seeders;

use App\Actions\Catalog\SaveProduct;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Media\ImageProcessor;
use App\Services\Media\MediaService;
use App\Services\Navigation\MenuRepository;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

/**
 * The FreshNest store (organic grocery), taken from the design (freshnest-html): store
 * details, delivery and payment settings, 8 categories, 4 growers (brands), 16 products with
 * pack sizes, the menus and the content pages. The data in freshnest/design-data.json was
 * read from the design's own code.
 *
 * The design's photos are on Unsplash. Products and categories start with the design's own
 * placeholder drawings (freshnest/images); `php artisan store:import-design-images` swaps in
 * the real photos when the server can reach the internet.
 */
class FreshnestSeeder extends Seeder
{
    public const DATA = __DIR__.'/freshnest/design-data.json';

    public const IMAGES = __DIR__.'/freshnest/images';

    /** Uploaded placeholders are named like this, so the photo import knows it may replace them. */
    public const PLACEHOLDER_PREFIX = 'freshnest-placeholder-';

    /** The FreshNest logo as uploaded by the seeder (Settings → Store details → Logo). */
    public const LOGO_PATH = 'settings/daily-basket-logo.png';

    public const LOGO_WHITE_PATH = 'settings/daily-basket-logo-white.png';

    public static function data(): array
    {
        return json_decode(file_get_contents(self::DATA), true);
    }

    public function run(SaveProduct $save, MediaService $media, SettingsService $settings): void
    {
        if (Product::withTrashed()->exists()) {
            return; // never add demo products to a store that already has products
        }

        $data = self::data();
        $this->settings($settings);
        self::logo($settings);
        $this->menus();
        $this->pages($data);

        $canDraw = ImageProcessor::isSupported();
        $placeholder = function (string $file, string $alt) use ($media, $canDraw): ?int {
            $path = self::IMAGES.'/'.$file;

            return $canDraw && is_file($path) ? $media->storeFromPath($path, self::PLACEHOLDER_PREFIX.$file, ['en' => $alt])->id : null;
        };

        // Categories, in the design's order ("Shop by Category").
        $categoryIds = [];
        foreach ($data['CATEGORIES'] as $position => $cat) {
            $page = $data['PAGES'][$cat['slug']] ?? null;
            $categoryIds[$cat['slug']] = Category::query()->create([
                'name' => ['en' => $cat['name']], 'slug' => $cat['slug'],
                'description' => ['en' => $page['blurb'] ?? ''],
                'image_id' => $placeholder($cat['placeholder'], $cat['name']),
                'is_active' => true, 'sort_order' => $position,
            ])->id;
        }

        // Growers are the store's brands (Catalog → Brands): "Grown by Sunridge Orchards".
        $growers = [];
        foreach ($data['GROWERS'] as $position => $g) {
            $growers[$g['cat']] = Brand::query()->create([
                'name' => ['en' => $g['name']], 'slug' => $g['slug'], 'origin' => ['en' => $g['origin']],
                'description' => ['en' => $g['blurb']], 'position' => $position,
            ]);
        }

        // Products, each with the design's three pack sizes (single, double, family pack).
        foreach ($data['PRODUCTS'] as $p) {
            $grower = $growers[$p['cat']];
            $short = str_replace('{farm}', $grower->getTranslation('name', 'en'), $data['SHORT'][$p['cat']]);
            $packs = [];
            foreach ($data['PACKS']['labels'] as $i => $label) {
                $packs[] = ['key' => "o0v{$i}", 'value' => ['en' => str_replace('{weight}', $p['weight'], $label)]];
            }

            $product = $save->handle([
                'name' => ['en' => $p['name']],
                'slug' => \Illuminate\Support\Str::slug($p['name']),
                'short_description' => ['en' => $short],
                'description' => ['en' => '<p>'.e($short.' Every batch is traced back to the field or kitchen it came from, and the grower\'s name is printed on the label.').'</p>'
                    .'<p>We buy direct and pay growers above market rate, which is why the price stays fair for you and the farm. Packed in compostable fibre and delivered on electric vans across the city.</p>'],
                'status' => 'active',
                'is_featured' => false,
                'brand' => $grower->getTranslation('name', 'en'),
                'category_ids' => [$categoryIds[$p['cat']]],
                'media_ids' => array_filter([$placeholder('prod-'.$p['id'].'.png', $p['name'])]),
                'options' => [['key' => 'o0', 'name' => ['en' => 'Pack size'], 'values' => $packs]],
                'variants' => collect($data['PACKS']['multipliers'])->map(fn ($m, $i) => [
                    'option_keys' => ["o0v{$i}"],
                    'price' => number_format(round($p['price'] * $m, 2), 2, '.', ''),
                    'compare_at_price' => number_format(round($p['old'] * $m, 2), 2, '.', ''),
                    'cost_price' => null,
                    'sku' => $p['sku'].($i ? '-'.($i + 1) : ''),
                    'barcode' => null, 'weight_grams' => null,
                    'track_inventory' => true, 'allow_backorder' => false, 'is_active' => true,
                    'stock_quantity' => $p['stock'], 'media_id' => null,
                ])->all(),
            ]);

            $product->forceFill([
                'brand_id' => $grower->id,
                'is_featured' => in_array($p['name'], $data['FEATURED'] ?? [], true),
                'rating_average' => number_format($p['rating'], 2, '.', ''),
                'rating_count' => (int) $p['reviews'],
            ])->saveQuietly();
        }

        $this->homepage($data);
        $this->productDetails($data);
        self::arabic($data);
    }

    /**
     * Arabic for the demo content (freshnest/arabic.json): categories, growers, products,
     * pack sizes, menus, banners, deals, pages and store details. Fills only Arabic texts
     * that are still empty, so anything typed in the admin is kept. Shown when Arabic is on.
     */
    public static function arabic(array $data): void
    {
        $ar = json_decode(file_get_contents(__DIR__.'/freshnest/arabic.json'), true);
        $t = $ar['text'];
        $fill = function ($model, array $fields) use ($t) {
            $changed = false;
            foreach ($fields as $field) {
                $en = (string) $model->getTranslation($field, 'en', false);
                if ($en !== '' && isset($t[$en]) && blank($model->getTranslation($field, 'ar', false))) {
                    $model->setTranslation($field, 'ar', $t[$en]);
                    $changed = true;
                }
            }
            if ($changed) {
                $model->saveQuietly();
            }
        };

        foreach (Category::query()->get() as $m) { $fill($m, ['name', 'description']); }
        foreach (Brand::query()->get() as $m) { $fill($m, ['name', 'origin', 'description']); }
        foreach (\App\Models\MenuItem::query()->get() as $m) { $fill($m, ['label']); }
        foreach (Banner::query()->get() as $m) { $fill($m, ['eyebrow', 'title', 'text', 'button_label']); }
        foreach (Promotion::query()->get() as $m) { $fill($m, ['name']); }
        foreach (\App\Models\ProductOption::query()->get() as $m) { $fill($m, ['name']); }
        foreach (\App\Models\ProductOptionValue::query()->get() as $m) {
            $en = (string) $m->getTranslation('value', 'en', false);
            if (blank($m->getTranslation('value', 'ar', false)) && $en !== '') {
                $m->setTranslation('value', 'ar', $t[$en] ?? $en)->saveQuietly();
            }
        }

        // Products: name, and the short text, description and nutrition in Arabic.
        foreach (Product::query()->with(['categories', 'brandRecord'])->get() as $product) {
            $fill($product, ['name']);
            $cat = $product->categories->first()?->slug;
            $farm = $product->brandRecord?->getTranslation('name', 'ar', false) ?: $product->brandRecord?->getTranslation('name', 'en', false);
            if (! $cat || ! isset($ar['short'][$cat])) {
                continue;
            }
            $short = str_replace('{farm}', (string) $farm, $ar['short'][$cat]);
            $changed = false;
            if (blank($product->getTranslation('short_description', 'ar', false))) {
                $product->setTranslation('short_description', 'ar', $short); $changed = true;
            }
            if (blank($product->getTranslation('description', 'ar', false))) {
                $product->setTranslation('description', 'ar', '<p>'.e($short.' '.$ar['desc_tail']).'</p><p>'.e($ar['desc_fair']).'</p>'
                    .'<h3>'.e($ar['storage']).'</h3><ul>'.collect($ar['tips'][$cat])->map(fn ($x) => '<li>'.e($x).'</li>')->implode('').'</ul>');
                $changed = true;
            }
            if (isset($data['NUTRITION'][$cat]) && blank($product->getTranslation('dimensions', 'ar', false))) {
                $product->setTranslation('dimensions', 'ar', '<p>'.e($ar['per100']).'</p><ul>'.collect($data['NUTRITION'][$cat])
                    ->map(fn ($r) => '<li>'.e($ar['nutrition'][$r[0]] ?? $r[0]).' — '.e($r[1]).'</li>')->implode('').'</ul>');
                $changed = true;
            }
            if ($changed) {
                $product->saveQuietly();
            }
        }

        // Pages: title, intro and the page text built from the Arabic sections.
        foreach (Page::query()->withoutGlobalScopes()->get() as $page) {
            $fill($page, ['title', 'intro']);
            if (filled($page->getTranslation('body', 'ar', false))) {
                continue;
            }
            $info = $data['INFO'][$page->slug] ?? null;
            $body = '';
            if ($info) {
                if ($info['stats']) {
                    $body .= '<ul>'.collect($info['stats'])->map(fn ($x) => '<li><strong>'.e($t[$x['v']] ?? $x['v']).'</strong> '.e($ar['stats'][$x['l']] ?? $x['l']).'</li>')->implode('').'</ul>';
                }
                foreach ($info['sections'] as $sec) {
                    [$h, $b] = $ar['sections'][$sec['h']] ?? [$sec['h'], $sec['b']];
                    $body .= '<h2>'.e($h).'</h2><p>'.e($b).'</p>';
                }
            }
            if ($page->slug === 'faq') {
                foreach ($ar['faq'] as [$q, $a]) { $body .= '<h2>'.e($q).'</h2><p>'.e($a).'</p>'; }
            }
            if ($page->slug === 'careers') {
                $j = $ar['jobs'];
                $body .= '<h2>'.e($ar['sections']['Open roles'][0]).'</h2><ul>'.collect($data['JOBS'])->map(fn ($r) => '<li><strong>'.e($j[$r[0]] ?? $r[0]).'</strong> — '.e($j[$r[1]] ?? $r[1]).' · '.e($j[$r[2]] ?? $r[2]).' · '.e($j[$r[3]] ?? $r[3]).'</li>')->implode('').'</ul><p>'.e($j['apply']).'</p>';
            }
            if ($body !== '') {
                $page->setTranslation('body', 'ar', $body)->saveQuietly();
            }
        }

        // Store details and delivery names.
        $settings = app(SettingsService::class);
        foreach (['general' => ['store_name', 'tagline', 'meta_title', 'meta_description', 'address', 'opening_hours'], 'shipping' => ['flat_name', 'flat_description']] as $group => $keys) {
            $values = [];
            foreach ($keys as $key) {
                $current = $settings->get("{$group}.{$key}");
                if (is_array($current) && blank($current['ar'] ?? null) && isset($t[$current['en'] ?? ''])) {
                    $values[$key] = $current + ['ar' => $t[$current['en']]];
                }
            }
            if ($values) {
                $settings->setMany($group, $values);
            }
        }
    }

    /**
     * Product-page details from the design: nutrition (Dimensions & Materials field), the
     * "Storage & use" list (end of the description) and three reviews each. Only fills what
     * is still empty, so it is safe on a store that has been edited.
     */
    public function productDetails(array $data): void
    {
        foreach (Product::query()->with('categories')->get() as $product) {
            $cat = $product->categories->first()?->slug;
            $changes = [];
            if ($cat && isset($data['NUTRITION'][$cat]) && blank($product->getTranslation('dimensions', 'en', false))) {
                $changes['dimensions'] = ['en' => '<p>Per 100 g</p><ul>'.collect($data['NUTRITION'][$cat])->map(fn ($r) => '<li>'.e($r[0]).' — '.e($r[1]).'</li>')->implode('').'</ul>'];
            }
            $desc = (string) $product->getTranslation('description', 'en', false);
            if ($cat && isset($data['TIPS'][$cat]) && ! str_contains($desc, 'Storage')) {
                $changes['description'] = ['en' => $desc.'<h3>Storage &amp; use</h3><ul>'.collect($data['TIPS'][$cat])->map(fn ($t) => '<li>'.e($t).'</li>')->implode('').'</ul>'];
            }
            if ($changes) {
                $product->fill($changes)->saveQuietly();
            }

            if (! $product->reviews()->exists()) {
                foreach ($data['REVIEWS'] as $i => $r) {
                    $at = now()->modify($r['ago'])->subMinutes($i);
                    \App\Models\ProductReview::query()->insert([
                        'product_id' => $product->id, 'user_id' => null, 'name' => $r['name'], 'rating' => $r['rating'], 'title' => null,
                        'body' => $r['body'], 'status' => 'approved', 'verified_purchase' => true, 'locale' => 'en', 'created_at' => $at, 'updated_at' => $at,
                    ]);
                }
                // The stored score and count follow the reviews actually shown.
                \App\Models\ProductReview::refreshProductRating($product->id);
            }
        }
    }

    /** Homepage banners, deals and discount codes, as the design shows them. */
    public function homepage(array $data): void
    {
        $order = [];
        foreach ($data['BANNERS'] as $b) {
            $position = $order[$b['placement']] = ($order[$b['placement']] ?? -1) + 1;
            Banner::query()->create([
                'placement' => $b['placement'],
                'eyebrow' => isset($b['eyebrow']) ? ['en' => $b['eyebrow']] : null,
                'title' => ['en' => $b['title']],
                'text' => isset($b['text']) ? ['en' => $b['text']] : null,
                'button_label' => isset($b['button']) ? ['en' => $b['button']] : null,
                'url' => $b['url'] ?? null,
                // The weekend offer counts down to Sunday night; set a new end date in Content → Banners.
                'ends_at' => ($b['ends'] ?? null) === 'weekend' ? now(config('store.display_timezone', 'UTC'))->endOfWeek()->utc() : null,
                'is_active' => true,
                'sort_order' => $position,
            ]);
        }

        $ids = fn (array $names) => Product::query()->get()->filter(fn ($p) => in_array($p->getTranslation('name', 'en'), $names, true))
            ->sortBy(fn ($p) => array_search($p->getTranslation('name', 'en'), $names, true))->pluck('id')->values()->all();
        foreach ($data['DEALS'] as $deal) {
            Promotion::query()->create([
                'name' => ['en' => $deal['name']], 'buy_quantity' => $deal['buy'], 'get_quantity' => $deal['get'], 'get_percent' => $deal['percent'],
                'applies_to' => isset($deal['category']) ? 'category' : 'products',
                'category_id' => isset($deal['category']) ? Category::query()->where('slug', $deal['category'])->value('id') : null,
                'product_ids' => isset($deal['products']) ? $ids($deal['products']) : null,
                'is_active' => true,
            ]);
        }

        foreach ($data['DISCOUNTS'] as $d) {
            Discount::query()->updateOrCreate(['code' => $d['code']], [
                'type' => $d['type'], 'value' => $d['value'], 'min_subtotal' => $d['min'] ?? null,
                'usage_limit_per_customer' => $d['per_customer'] ?? null, 'is_active' => true,
            ]);
        }
    }

    /**
     * Puts the FreshNest logo in Settings → Store details (logo and logo on dark), so the admin
     * sign-in, the admin and the emails show it. Leaves a logo the owner uploaded alone.
     */
    public static function logo(SettingsService $settings): void
    {
        if (filled($settings->get('general.logo'))) {
            return;
        }
        $disk = \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'));
        $disk->put(self::LOGO_PATH, file_get_contents(self::IMAGES.'/'.basename(self::LOGO_PATH)));
        $disk->put(self::LOGO_WHITE_PATH, file_get_contents(self::IMAGES.'/'.basename(self::LOGO_WHITE_PATH)));
        $settings->setMany('general', ['logo' => self::LOGO_PATH, 'logo_white' => self::LOGO_WHITE_PATH]);
    }

    /**
     * US dollars are the base (rate 1, marked "Base" in Settings → Currencies); the other
     * currencies are priced against the dollar and start switched off. Switch one on and the
     * storefront shows a currency picker. Only when the store's base currency is USD.
     */
    public static function currencies(): void
    {
        if (config('store.base_currency') !== 'USD') {
            return;
        }
        $rates = ['USD' => ['1', '$', '$', 0, true], 'AED' => ['3.6725', 'AED', 'د.إ', 1, false], 'EUR' => ['0.92', '€', '€', 2, false], 'SAR' => ['3.75', 'SAR', 'ر.س', 3, false]];
        foreach ($rates as $code => [$rate, $symbol, $symbolAr, $order, $base]) {
            $currency = \App\Models\Currency::query()->firstOrNew(['code' => $code]);
            if (! $currency->exists) {
                $currency->name = ['en' => ['USD' => 'US Dollar', 'AED' => 'UAE Dirham', 'EUR' => 'Euro', 'SAR' => 'Saudi Riyal'][$code]];
            }
            $currency->forceFill(['symbol' => $symbol, 'symbol_ar' => $symbolAr, 'exchange_rate' => $rate, 'sort_order' => $order, 'is_base' => $base, 'is_active' => $base])->save();
        }
        app(\App\Services\Currency\CurrencyService::class)->flush();
    }

    private function settings(SettingsService $settings): void
    {
        self::currencies();

        // Addresses start in the United States (Settings → Shipping → Default country).
        if (blank($settings->get('shipping.default_country'))) {
            $settings->setMany('shipping', ['default_country' => 'US']);
        }

        // Store details as the design gives them — Settings → Store details.
        $settings->setMany('general', [
            'store_name' => ['en' => 'Daily Basket'],
            'tagline' => ['en' => 'Fresh from the farm in 45 minutes'],
            'meta_title' => ['en' => 'Daily Basket Organic Grocery | Delivered in 45 Minutes'],
            'meta_description' => ['en' => 'Organic groceries from growers within 80 miles, delivered in 45 minutes. Certified organic, plastic-free packaging, carbon-neutral routes.'],
            'locales' => ['en'],
            'announcement_enabled' => false,
            'store_email' => 'hello@dailybasket.example',
            'store_phone' => '+1 (800) 555-0132',
            'whatsapp' => null,
            'address' => ['en' => "18 Meadow Row\nSan Francisco, CA 94110"],
            'opening_hours' => ['en' => 'Mon–Sun, 7am–10pm'],
            'instagram_url' => 'https://instagram.com/',
            'facebook_url' => 'https://facebook.com/',
            'x_url' => 'https://x.com/',
            'youtube_url' => 'https://youtube.com/',
            'tiktok_url' => null,
        ]);

        // Delivery: $4.99, free over $50 (the design's figures) — Settings → Shipping.
        $settings->setMany('shipping', [
            'flat_enabled' => true,
            'flat_name' => ['en' => 'Express delivery'],
            'flat_description' => ['en' => 'Same day, in about 45 minutes'],
            'flat_rate' => '4.99',
            'free_enabled' => true,
            'free_threshold' => '50.00',
        ]);

        // Payments: cash on delivery, and card (Stripe) once its keys are set — Settings → Payments.
        $settings->setMany('payments', [
            'cod_enabled' => true,
            'stripe_enabled' => filled(config('services.stripe.secret')),
        ]);

        // US prices are shown as they are; no UAE VAT.
        $settings->setMany('tax', ['enabled' => false]);
    }

    private function menus(): void
    {
        $menus = [
            'header' => ['Header', [
                ['Home', '/', [['Homepage', '/'], ['Popular Products', '/#fruits'], ['Deals of the Day', '/#dairy']]],
                ['Shop', '/shop', [['All Products', '/shop'], ['Fruits', '/collections/fruits'], ['Vegetables', '/collections/vegetables'], ['Dairy', '/collections/dairy'], ['Bakery', '/collections/bakery']]],
                ['Offers', '/pages/offers'],
                ['Contact', '/pages/contact'],
            ]],
            // Name = "small line|big line"; item labels = "text|badge".
            'header-deals' => ['Only This Weekend|Super Discount', [
                ['Organic fruit & veg|30% OFF', '/collections/fruits'],
                ['Fresh dairy|BUY 2 GET 1', '/collections/dairy'],
                ['Morning bakery|20% OFF', '/collections/bakery'],
            ]],
            'footer-shop' => ['Shop', [
                ['Fruits & Berries', '/collections/fruits'], ['Vegetables', '/collections/vegetables'], ['Dairy & Eggs', '/collections/dairy'],
                ['Bakery', '/collections/bakery'], ['Weekly Offers', '/pages/offers'],
            ]],
            'footer-company' => ['Company', [
                ['Our Growers', '/pages/our-growers'], ['Sustainability', '/pages/sustainability'], ['Careers', '/pages/careers'],
            ]],
            'footer-help' => ['Help', [
                ['Delivery Info', '/pages/delivery-info'], ['Returns & Refunds', '/pages/returns-refunds'], ['Track Order', '/account/orders'], ['FAQs', '/pages/faq'],
            ]],
        ];

        foreach ($menus as $handle => [$name, $items]) {
            $menu = Menu::query()->updateOrCreate(['handle' => $handle], ['name' => $name]);
            $menu->items()->delete();
            $this->menuItems($menu, $items);
        }

        app(MenuRepository::class)->flush();
    }

    private function menuItems(Menu $menu, array $items, ?int $parentId = null): void
    {
        foreach ($items as $position => $item) {
            $created = $menu->items()->create(['parent_id' => $parentId, 'label' => ['en' => $item[0]], 'url' => $item[1], 'sort_order' => $position]);
            if (! empty($item[2])) {
                $this->menuItems($menu, $item[2], $created->id);
            }
        }
    }

    /** The content pages: headline and intro from the design; sections as the page text. */
    private function pages(array $data): void
    {
        // "sale" holds the headline of /offers (the sale listing); hidden like "shop".
        $data['PAGES']['sale'] = $data['PAGES']['offers'];
        $slugs = ['shop', 'sale', 'offers', 'contact', 'faq', 'delivery-info', 'returns-refunds', 'our-growers', 'sustainability', 'careers', 'privacy-policy', 'terms-conditions'];

        foreach ($slugs as $slug) {
            $meta = $data['PAGES'][$slug];
            $info = $data['INFO'][$slug] ?? ['stats' => [], 'sections' => [], 'cards' => []];

            $body = '';
            if ($info['stats']) {
                $body .= '<ul>'.collect($info['stats'])->map(fn ($s) => '<li><strong>'.e($s['v']).'</strong> '.e($s['l']).'</li>')->implode('').'</ul>';
            }
            foreach ($info['sections'] as $section) {
                $body .= '<h2>'.e($section['h']).'</h2><p>'.e($section['b']).'</p>';
            }
            if ($slug === 'faq') {
                foreach ($data['FAQS'] as [$q, $a]) {
                    $body .= '<h2>'.e($q).'</h2><p>'.e($a).'</p>';
                }
            }
            if ($slug === 'careers') {
                $body .= '<h2>Open roles</h2><ul>'.collect($data['JOBS'])->map(fn ($j) => '<li><strong>'.e($j[0]).'</strong> — '.e($j[1]).' · '.e($j[2]).' · '.e($j[3]).'</li>')->implode('').'</ul>'
                    .'<p>To apply, email careers@dailybasket.example with the role in the subject line.</p>';
            }

            $page = Page::query()->withoutGlobalScopes()->firstOrNew(['slug' => $slug]);
            $page->is_active = ! in_array($slug, ['shop', 'sale'], true);
            $page->fill([
                'title' => ['en' => $meta['title']],
                'intro' => ['en' => $meta['blurb']],
                'body' => $body !== '' ? ['en' => $body] : null,
                'show_contact_form' => $slug === 'contact',
            ])->save();
        }
    }
}
