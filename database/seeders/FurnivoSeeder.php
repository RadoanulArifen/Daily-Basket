<?php

namespace Database\Seeders;

use App\Actions\Catalog\SaveProduct;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Media\ImageProcessor;
use App\Services\Media\MediaService;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The Furnivo store, imported from the design (site.zip): categories, brands, 104 products,
 * home hero slides, product rows and content pages. The data in furnivo/design-data.json was
 * read from the design's own code (see tools/theme-import).
 *
 * Product and category photos in the design come from Unsplash: run
 * `php artisan store:import-design-images` on the server to download them.
 */
class FurnivoSeeder extends Seeder
{
    public const DATA = __DIR__.'/furnivo/design-data.json';

    /** Design category id → URL handle (matches the design's page names). */
    public const CATEGORY_SLUGS = ['seating' => 'seating', 'tables' => 'tables', 'storage' => 'storage-cabinetry', 'bedroom' => 'beds-bedroom', 'lighting' => 'lighting'];

    /** "Configure your piece" option sets per category (from the design). */
    public const OPTIONS_BY_CATEGORY = [
        'seating' => [['Dimensions', ['As shown', '2-seat', '3-seat', '+ Custom size']], ['Frame', ['Ash', 'Oak', 'Walnut']], ['Finish', ['Matte black', 'Brushed brass', 'Natural']], ['Upholstery', ['Fabric', 'Bouclé', 'Leather']], ['Configuration', ['Standard', 'Left-facing', 'Right-facing']]],
        'tables' => [['Dimensions', ['As shown', '+ Custom size']], ['Top', ['Walnut', 'Oak', 'Marble']], ['Finish', ['Matte black', 'Brushed brass', 'Natural']], ['Seats', ['4', '6', '8+']]],
        'storage' => [['Dimensions', ['As shown', '+ Custom size']], ['Material', ['Oak', 'Walnut', 'Lacquer']], ['Finish', ['Matte black', 'Brushed brass', 'Natural']], ['Configuration', ['Doors', 'Drawers', 'Mixed']]],
        'bedroom' => [['Dimensions', ['Queen', 'King', 'Super King', '+ Custom size']], ['Material', ['Ash', 'Oak', 'Walnut']], ['Finish', ['Matte black', 'Brushed brass', 'Natural']], ['Upholstery', ['Fabric', 'Bouclé', 'Leather']]],
        'lighting' => [['Size', ['As shown', '+ Custom size']], ['Material', ['Glass', 'Brass', 'Aluminium']], ['Finish', ['Matte black', 'Brushed brass', 'Natural']], ['Bulb', ['Warm white', 'Neutral', 'Dimmable']]],
    ];

    public const COLOURS = ['Walnut' => '#6b4423', 'Charcoal' => '#3a3a3a', 'Natural Oak' => '#c9a877', 'Ivory' => '#efe9dc', 'Forest' => '#a7b6a0'];

    /** The design's sample reviews: the same lists and rule its product page uses. */
    public const REVIEW_NAMES = ['Layla A.', 'Omar R.', 'Sofia M.', 'Daniel K.', 'Aisha N.', 'Marcus T.', 'Priya S.', 'Khalid H.', 'Elena V.', 'Yusuf B.', 'Nadia F.', 'Thomas L.', 'Reem K.', 'Adam J.', 'Mariam Z.'];

    public const REVIEW_BODIES = [
        'Exceptional craftsmanship — the materials feel genuinely luxurious and it anchors the whole room.',
        'Delivery and white-glove installation were flawless. The finish is even better in person.',
        'Worth every dollar. Solid, beautifully made, and the proportions are perfect for our home.',
        'Ordered a custom size and the team kept me updated throughout. Superb result.',
        'Understated and elegant. Exactly the quiet-luxury feel we were after.',
        'Beautiful piece, though it arrived a few days later than estimated. Quality made up for it.',
        'The detailing is remarkable and it feels built to last a lifetime. Highly recommend.',
        'Great communication from the concierge team. The colour matched the swatch perfectly.',
        'A real statement piece. Guests always comment on it. Very happy with the purchase.',
        'Comfortable, sturdy and genuinely premium. Packaging and setup were first class.',
    ];

    /** "2 weeks ago", "1 month ago", … */
    public const REVIEW_AGES = ['-2 weeks', '-1 month', '-2 months', '-3 months', '-5 months', '-6 months', '-8 months'];

    /** Sample reviews for one product — also used by `php artisan store:demo-reviews`. */
    public function addDemoReviews(Product $product): void
    {
        $this->seedReviews($product);
    }

    private function seedReviews(Product $product): void
    {
        $count = 11 + (mb_strlen($product->getTranslation('name', 'en')) % 4);
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $at = now()->modify(self::REVIEW_AGES[$i % 7])->subMinutes($i);
            $rows[] = [
                'product_id' => $product->id, 'user_id' => null, 'name' => self::REVIEW_NAMES[$i % 15],
                'rating' => $i % 7 === 3 ? 3 : ($i % 3 === 0 ? 5 : 4), 'title' => null, 'body' => self::REVIEW_BODIES[$i % 10],
                'status' => 'approved', 'verified_purchase' => true, 'locale' => 'en', 'created_at' => $at, 'updated_at' => $at,
            ];
        }
        // Inserted last-first: the page lists newest-submitted first, which gives the design's order.
        \App\Models\ProductReview::query()->insert(array_reverse($rows));
    }

    /** The design's category blurbs, shown on the category page. */
    public const CATEGORY_BLURBS = [
        'seating' => 'Sofas, sectionals and chairs — from statement leather to quiet bouclé, built for the way you live.',
        'tables' => 'Dining, coffee and console tables in marble, walnut and stone. Standard, or made to your exact span.',
        'storage' => 'Sideboards, cabinetry and shelving that hold everything and hide nothing beautiful.',
        'bedroom' => 'Beds, nightstands and dressers for a room that rests as well as it reads.',
        'lighting' => 'Chandeliers, pendants and lamps — hand-blown glass and machined brass, ready to ship.',
    ];

    public static function configurationFor(string $category): array
    {
        return array_merge(
            [['label' => 'Colour', 'values' => array_keys(self::COLOURS), 'swatches' => self::COLOURS]],
            array_map(fn ($o) => ['label' => $o[0], 'values' => $o[1]], self::OPTIONS_BY_CATEGORY[$category] ?? []),
        );
    }

    public function run(SaveProduct $save, MediaService $media, SettingsService $settings): void
    {
        if (Product::withTrashed()->exists()) {
            return; // never add demo products to a store that already has products
        }

        $data = json_decode(file_get_contents(self::DATA), true);

        // A US-dollar store: dollars are the base (rate 1, "$"); other currencies off.
        if (config('store.base_currency') === 'USD') {
            \App\Models\Currency::query()->updateOrCreate(['code' => 'USD'], ['name' => ['en' => 'US Dollar'], 'symbol' => '$', 'exchange_rate' => '1', 'is_active' => true, 'sort_order' => 0]);
            \App\Models\Currency::query()->where('code', '!=', 'USD')->update(['is_active' => false]);
            app(\App\Services\Currency\CurrencyService::class)->flush();
        }

        $settings->setMany('general', [
            'store_name' => ['en' => 'Furnivo'],
            'tagline' => ['en' => 'Luxury Furniture & Lighting, Made to Your Measure'],
            'meta_title' => ['en' => 'Luxury Furniture & Lighting, Made to Your Measure | Furnivo'],
            'meta_description' => ['en' => 'Furnivo curates and builds luxury furniture and lighting — seating, tables, storage, bedroom and lighting, as shown or made to your specification.'],
            'announcement_enabled' => false,
            // The store's own details, as the design gives them. Change these in
            // Settings → Store details; the contact page and emails follow them.
            'store_email' => 'hello@furnivo.com',
            'store_phone' => '+44 20 7000 0000',
            'whatsapp' => '+44 7700 900000',
            'address' => ['en' => "48 Wigmore Street, Marylebone\nLondon, United Kingdom"],
            'opening_hours' => ['en' => "Sat–Thu 10:00–20:00\nFri 14:00–20:00"],
        ]);

        // Categories and subcategories, in the design's order.
        $categoryIds = [];
        foreach ($data['CATS'] as $position => $cat) {
            $parent = Category::query()->create(['name' => ['en' => $cat['name']], 'slug' => self::CATEGORY_SLUGS[$cat['id']] ?? Str::slug($cat['name']), 'description' => ['en' => self::CATEGORY_BLURBS[$cat['id']] ?? ''], 'is_active' => true, 'sort_order' => $position]);
            $categoryIds[$cat['id']] = $parent->id;
            foreach ($cat['subs'] as $i => $sub) {
                $child = Category::query()->create(['name' => ['en' => $sub], 'slug' => Str::slug($sub), 'parent_id' => $parent->id, 'is_active' => true, 'sort_order' => $i]);
                $categoryIds[$cat['id'].'|'.$sub] = $child->id;
            }
        }

        // Brands with their logos (shipped in public/themes/furnivo).
        $brandIds = [];
        foreach (array_values($data['BRANDS']) as $position => $b) {
            $brandIds[$b['id']] = Brand::query()->create([
                'name' => ['en' => $b['name']], 'slug' => $b['id'], 'origin' => ['en' => $b['origin'] ?? ''], 'description' => ['en' => $b['blurb'] ?? ''],
                'logo' => isset($b['logo']) ? 'themes/furnivo/'.basename($b['logo']) : null,
                'logo_small' => isset($b['logoSmall']) ? 'themes/furnivo/'.basename($b['logoSmall']) : null,
                'position' => $position,
            ])->id;
        }

        // Products. Two have their photo in the design itself; the rest come from Unsplash.
        $canDraw = ImageProcessor::isSupported();
        foreach ($data['PRODUCTS'] as $index => $p) {
            $mediaIds = [];
            if ($canDraw && str_starts_with($p['image'], 'assets/') && is_file($file = public_path('themes/furnivo/'.basename($p['image'])))) {
                $mediaIds[] = $media->storeFromPath($file, basename($file), ['en' => $p['name']])->id;
            }

            $product = $save->handle([
                'name' => ['en' => $p['name']],
                'slug' => $p['id'],
                'short_description' => ['en' => $p['spec']],
                // The design's "Dimensions & Materials" text for this piece.
                'dimensions' => ['en' => '<p>Materials: '.e(rtrim($p['spec'], '.')).'. Made-to-order dimensions available — specify your exact size, finish and configuration via Custom Order.</p>'],
                'description' => ['en' => $p['spec'].'. Crafted by '.($data['BRANDS'][$p['brand']]['name'] ?? 'our atelier').' with an uncompromising eye for material and proportion. Every piece is finished by hand and built to endure.'],
                'status' => 'active',
                'is_featured' => false,
                'brand' => $data['BRANDS'][$p['brand']]['name'] ?? null,
                'category_ids' => [$categoryIds[$p['cat'].'|'.$p['sub']]],
                'media_ids' => $mediaIds,
                'options' => [],
                'variants' => [[
                    'option_keys' => [], 'price' => number_format($p['price'], 2, '.', ''), 'compare_at_price' => null, 'cost_price' => null,
                    'sku' => strtoupper(substr($p['brand'], 0, 3)).'-'.(1001 + $index),   // the design's product code 'barcode' => null, 'weight_grams' => null,
                    'track_inventory' => ! $p['madeToOrder'], 'allow_backorder' => false, 'is_active' => true,
                    'stock_quantity' => $p['madeToOrder'] ? 0 : 10, 'media_id' => null,
                ]],
            ]);

            $product->forceFill([
                'brand_id' => $brandIds[$p['brand']] ?? null,
                'made_to_order' => (bool) $p['madeToOrder'],
                'from_price' => (bool) $p['fromPrice'],
                'configuration' => self::configurationFor($p['cat']),
                'rating_average' => number_format($p['rating'], 2, '.', ''),
                'rating_count' => (int) $p['reviews'],
            ])->saveQuietly();

            $this->seedReviews($product);
            // The stored score and count follow the reviews actually written, so the
            // product page never claims more reviews than it can show.
            \App\Models\ProductReview::refreshProductRating($product->id);
        }

        // Product rows on the home page, in the design's order (Online Store → Customize).
        $settings->setMany('furnivo', [
            'rails' => array_map(fn ($r) => ['key' => $r['id'], 'eyebrow' => $r['eyebrow'], 'title' => $r['title'], 'products' => $r['ids']], $data['RAILS']),
        ]);
        Product::query()->whereIn('slug', $data['RAILS'][0]['ids'] ?? [])->update(['is_featured' => true]);

        // The four tiles between the product rows (images: store:import-design-images).
        $explore = ['New In' => '/collections/new-in', 'Seating' => '/collections/seating', 'Tables' => '/collections/tables', 'Beds & Bedroom' => '/collections/beds-bedroom'];
        $settings->setMany('furnivo', [
            'explore' => array_map(fn ($t) => ['label' => $t['label'], 'url' => $explore[$t['label']] ?? '/shop', 'image_id' => null], $data['EXPLORE']),
        ]);

        // Hero slides (images are downloaded by store:import-design-images).
        foreach ($data['HERO'] as $i => $slide) {
            Banner::query()->create([
                'placement' => 'hero', 'title' => ['en' => $slide['title']], 'text' => ['en' => $slide['sub'] ?? ''],
                'eyebrow' => ['en' => $slide['eyebrow'] ?? ''], 'button_label' => ['en' => $slide['cta'] ?? 'Explore the collection'],
                'url' => '/shop', 'is_active' => true, 'sort_order' => $i,
            ]);
        }

        $this->call(FurnivoContentSeeder::class);
    }
}
