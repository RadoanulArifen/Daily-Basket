<?php

namespace Database\Seeders;

use App\Actions\Catalog\SaveProduct;
use App\Models\Category;
use App\Models\Product;
use App\Services\Media\ImageProcessor;
use App\Services\Media\MediaService;
use Illuminate\Database\Seeder;

/**
 * Demo catalogue for a starter store. Skipped in production and when products already exist.
 * Category slugs match the links in MenuSeeder.
 */
class CatalogSeeder extends Seeder
{
    public function run(SaveProduct $save, MediaService $media): void
    {
        if (app()->isProduction() || Product::withTrashed()->exists()) {
            return;
        }

        $categories = $this->categories();
        $canDraw = ImageProcessor::isSupported();

        if (! $canDraw) {
            $this->command?->warn('PHP GD with WebP is not enabled — demo products are created without images.');
        }

        foreach ($this->products() as $i => $p) {
            $mediaIds = $canDraw ? $this->storeImages($media, $p) : [];

            $options = [];
            foreach ($p['options'] ?? [] as $oi => [$nameEn, $nameAr, $values]) {
                $options[] = [
                    'key' => "o{$oi}",
                    'name' => ['en' => $nameEn, 'ar' => $nameAr],
                    'values' => collect($values)->map(fn ($v, $vi) => ['key' => "o{$oi}v{$vi}", 'value' => ['en' => $v[0], 'ar' => $v[1]]])->all(),
                ];
            }

            $variants = [];
            $combos = $this->combinations(array_map(fn ($o) => array_column($o['values'], 'key'), $options));
            foreach ($combos as $ci => $keys) {
                $variants[] = [
                    'option_keys' => $keys,
                    'price' => is_array($p['price']) ? $p['price'][$ci % count($p['price'])] : $p['price'],
                    'compare_at_price' => $p['compare_at'] ?? null,
                    'cost_price' => null,
                    'sku' => $p['sku'].($keys ? '-'.($ci + 1) : ''),
                    'barcode' => null,
                    'weight_grams' => $p['weight'],
                    'track_inventory' => true,
                    'allow_backorder' => false,
                    'is_active' => true,
                    'stock_quantity' => $p['stock'][$ci % count($p['stock'])],
                    // e.g. the Indigo cushion shows the indigo photo when chosen.
                    'media_id' => isset($p['image_option'])
                        ? ($mediaIds[array_search($keys[$p['image_option']], array_column($options[$p['image_option']]['values'], 'key'), true)] ?? null)
                        : null,
                ];
            }

            $save->handle([
                'name' => $p['name'],
                'short_description' => $p['short'],
                'description' => $p['description'],
                'status' => $p['status'] ?? 'active',
                'is_featured' => $p['featured'] ?? false,
                'brand' => 'Demo Store',
                'category_ids' => array_map(fn ($slug) => $categories[$slug], $p['categories']),
                'media_ids' => $mediaIds,
                'options' => $options,
                'variants' => $variants,
            ]);
        }

        $this->assignCategoryImages();
    }

    /** Folder of illustrated demo photos: {product-slug}-1.webp, -2.webp … */
    public const IMAGE_DIR = __DIR__.'/demo-images';

    /**
     * Store a demo product's images and return their media ids, in order.
     * Uses the illustrated photos in demo-images/, or draws simple ones if a file is missing.
     *
     * @return list<int>
     */
    public function storeImages(MediaService $media, array $p): array
    {
        $slug = (string) str($p['name']['en'])->slug();
        $alt = ['en' => $p['name']['en'], 'ar' => $p['name']['ar']];
        $files = glob(self::IMAGE_DIR."/{$slug}-*.webp") ?: [];
        natsort($files);
        $ids = [];

        foreach ($files as $file) {
            $ids[] = $media->storeFromPath($file, basename($file), $alt)->id;
        }

        if (! $ids) {
            foreach ($p['images'] as $n => $colours) {
                $file = DemoImage::draw($p['shape'], ...$colours);
                $ids[] = $media->storeFromPath($file, "{$slug}-".($n + 1).'.png', $alt)->id;
                @unlink($file);
            }
        }

        return $ids;
    }

    /** Each demo category gets a styled room photo from one of its products. */
    public const CATEGORY_IMAGES = [
        'living' => 'woven-wool-cushion-3.webp',
        'dining' => 'brass-dallah-2.webp',
        'gifts' => 'majlis-gift-box-2.webp',
        'cushions' => 'palm-frond-linen-cushion-2.webp',
        'throws' => 'desert-dune-throw-3.webp',
        'candles' => 'oud-amber-candle-2.webp',
        'serveware' => 'date-serving-platter-3.webp',
        'coffee-tea' => 'gahwa-cups-set-of-6-2.webp',
    ];

    public function assignCategoryImages(): void
    {
        foreach (self::CATEGORY_IMAGES as $slug => $file) {
            $mediaId = \App\Models\Media::query()->where('original_name', $file)->latest('id')->value('id');
            if ($mediaId) {
                Category::query()->where('slug', $slug)->update(['image_id' => $mediaId]);
            }
        }
    }

    /** The demo product definitions, for the demo-image refresh command. */
    public function definitions(): array
    {
        return $this->products();
    }

    /** @return array<string, int> slug => id */
    private function categories(): array
    {
        $tree = [
            ['living', 'Living', 'غرفة المعيشة', 'Cushions, throws and scents for the majlis and living room.', 'وسائد وأغطية وعطور للمجلس وغرفة المعيشة.', [
                ['cushions', 'Cushions', 'وسائد'],
                ['throws', 'Throws', 'أغطية'],
                ['candles', 'Candles & scents', 'شموع وعطور'],
            ]],
            ['dining', 'Dining', 'السفرة', 'Serveware for gahwa, dates and long lunches.', 'أدوات تقديم للقهوة والتمر والولائم.', [
                ['serveware', 'Serveware', 'أدوات التقديم'],
                ['coffee-tea', 'Coffee & tea', 'القهوة والشاي'],
            ]],
            ['gifts', 'Gifts', 'هدايا', 'Ready-to-give pieces, wrapped on request.', 'قطع جاهزة للإهداء، مع تغليف عند الطلب.', []],
        ];

        $ids = [];
        foreach ($tree as $position => [$slug, $en, $ar, $descEn, $descAr, $children]) {
            $parent = Category::create(['name' => ['en' => $en, 'ar' => $ar], 'slug' => $slug, 'description' => ['en' => $descEn, 'ar' => $descAr], 'sort_order' => $position]);
            $ids[$slug] = $parent->id;

            foreach ($children as $childPosition => [$childSlug, $childEn, $childAr]) {
                $ids[$childSlug] = Category::create(['parent_id' => $parent->id, 'name' => ['en' => $childEn, 'ar' => $childAr], 'slug' => $childSlug, 'sort_order' => $childPosition])->id;
            }
        }

        return $ids;
    }

    private function combinations(array $lists): array
    {
        return array_reduce($lists, fn ($acc, $list) => array_merge(...array_map(fn ($combo) => array_map(fn ($k) => [...$combo, $k], $list), $acc)), [[]]);
    }

    private function products(): array
    {
        $red = [0x9B2C24, 0xE8DFD0];
        $indigo = [0x2B3A55, 0xE8DFD0];
        $sand = [0xC9B08A, 0x22262A];
        $charcoal = [0x3A3D40, 0xC9B08A];
        $olive = [0x6B5B3E, 0xE8DFD0];
        $brass = [0xB08D3C, 0x22262A];
        $white = [0xF3EEE6, 0x9B2C24];
        $green = [0x2F4A3F, 0xE8DFD0];

        return [
            [
                'name' => ['en' => 'Woven Wool Cushion', 'ar' => 'وسادة صوف منسوجة'], 'sku' => 'SH-CUS-WOVEN', 'shape' => 'cushion',
                'short' => ['en' => 'Hand-woven wool cover in a traditional woven pattern.', 'ar' => 'غطاء صوف منسوج يدوياً بنقشة منسوجة تقليدية.'],
                'description' => ['en' => "Woven by artisans in Al Ain using a traditional Bedouin weaving technique.\n\nWool front, cotton back, hidden zip. Feather insert included.\n\nSpot clean only.", 'ar' => "نسجها حرفيون في العين بتقنية السدو البدوية.\n\nواجهة من الصوف وخلفية قطنية وسحّاب مخفي. الحشوة من الريش مرفقة.\n\nتنظيف موضعي فقط."],
                'options' => [['Colour', 'اللون', [['Deep red', 'أحمر داكن'], ['Indigo', 'نيلي']]], ['Size', 'المقاس', [['45 × 45 cm', '45 × 45 سم'], ['60 × 60 cm', '60 × 60 سم']]]],
                'price' => ['145.00', '185.00'], 'stock' => [12, 6, 9, 4], 'weight' => 900,
                'categories' => ['cushions', 'gifts'], 'featured' => true, 'images' => [$red, $indigo], 'image_option' => 0,
            ],
            [
                'name' => ['en' => 'Palm Frond Linen Cushion', 'ar' => 'وسادة كتان بنقشة سعف النخيل'], 'sku' => 'SH-CUS-PALM', 'shape' => 'cushion',
                'short' => ['en' => 'Washed linen, printed with palm fronds.', 'ar' => 'كتان مغسول مطبوع بسعف النخيل.'],
                'description' => ['en' => "Soft washed linen with a block-printed palm frond motif.\n\n45 × 45 cm. Machine washable at 30°C.", 'ar' => "كتان ناعم مغسول بطبعة سعف النخيل.\n\n45 × 45 سم. قابل للغسل في الغسالة على 30 درجة."],
                'price' => '120.00', 'compare_at' => '150.00', 'stock' => [18], 'weight' => 700,
                'categories' => ['cushions'], 'images' => [$green],
            ],
            [
                'name' => ['en' => 'Desert Dune Throw', 'ar' => 'غطاء كثبان الصحراء'], 'sku' => 'SH-THR-DUNE', 'shape' => 'throw',
                'short' => ['en' => 'A heavy cotton throw for cool desert evenings.', 'ar' => 'غطاء قطني ثقيل لأمسيات الصحراء الباردة.'],
                'description' => ['en' => "130 × 170 cm, 100% cotton with knotted fringes.\n\nMachine washable.", 'ar' => "130 × 170 سم، قطن 100% بأطراف معقودة.\n\nقابل للغسل في الغسالة."],
                'options' => [['Colour', 'اللون', [['Sand', 'رملي'], ['Charcoal', 'فحمي']]]],
                'price' => ['320.00'], 'stock' => [7, 5], 'weight' => 1400,
                'categories' => ['throws'], 'featured' => true, 'images' => [$sand, $charcoal], 'image_option' => 0,
            ],
            [
                'name' => ['en' => 'Striped Wool Throw', 'ar' => 'غطاء صوف مخطط'], 'sku' => 'SH-THR-WOOL', 'shape' => 'throw',
                'short' => ['en' => 'Striped lambswool throw.', 'ar' => 'غطاء من صوف الحملان بخطوط.'],
                'description' => ['en' => "140 × 190 cm lambswool. Dry clean only.", 'ar' => "صوف حملان بمقاس 140 × 190 سم. تنظيف جاف فقط."],
                'price' => '395.00', 'stock' => [4], 'weight' => 1200,
                'categories' => ['throws', 'gifts'], 'images' => [$red],
            ],
            [
                'name' => ['en' => 'Oud & Amber Candle', 'ar' => 'شمعة العود والعنبر'], 'sku' => 'SH-CAN-OUD', 'shape' => 'candle',
                'short' => ['en' => 'Smoky oud and warm amber in a hand-poured soy candle.', 'ar' => 'عود دخاني وعنبر دافئ في شمعة صويا مصبوبة يدوياً.'],
                'description' => ['en' => "Soy wax, cotton wick.\n\n200 g burns for about 40 hours; 400 g for about 80 hours.", 'ar' => "شمع الصويا وفتيل قطني.\n\n200 غ تشتعل نحو 40 ساعة، و400 غ نحو 80 ساعة."],
                'options' => [['Size', 'الحجم', [['200 g', '200 غ'], ['400 g', '400 غ']]]],
                'price' => ['95.00', '160.00'], 'stock' => [25, 10], 'weight' => 450,
                'categories' => ['candles', 'gifts'], 'featured' => true, 'images' => [$charcoal],
            ],
            [
                'name' => ['en' => 'Saffron Rose Candle', 'ar' => 'شمعة الزعفران والورد'], 'sku' => 'SH-CAN-ROSE', 'shape' => 'candle',
                'short' => ['en' => 'Taif rose with a thread of saffron.', 'ar' => 'ورد طائفي مع لمسة زعفران.'],
                'description' => ['en' => "Soy wax, 200 g, about 40 hours.", 'ar' => "شمع صويا، 200 غ، نحو 40 ساعة."],
                'price' => '85.00', 'stock' => [0], 'weight' => 300,
                'categories' => ['candles'], 'images' => [$white],
            ],
            [
                'name' => ['en' => 'Brass Dallah', 'ar' => 'دلة نحاسية'], 'sku' => 'SH-COF-DALL', 'shape' => 'dallah',
                'short' => ['en' => 'A polished brass dallah for serving gahwa.', 'ar' => 'دلة نحاسية مصقولة لتقديم القهوة العربية.'],
                'description' => ['en' => "Holds 1 litre. Tin-lined. Hand wash only.", 'ar' => "سعة لتر واحد. مبطنة بالقصدير. غسيل يدوي فقط."],
                'price' => '240.00', 'stock' => [8], 'weight' => 1100,
                'categories' => ['coffee-tea', 'gifts'], 'featured' => true, 'images' => [$brass],
            ],
            [
                'name' => ['en' => 'Gahwa Cups, Set of 6', 'ar' => 'فناجين قهوة، طقم من 6'], 'sku' => 'SH-COF-CUPS', 'shape' => 'cups',
                'short' => ['en' => 'Porcelain finjan cups with a gold rim.', 'ar' => 'فناجين من البورسلين بحافة ذهبية.'],
                'description' => ['en' => "Six cups, 60 ml each. Not microwave safe.", 'ar' => "ستة فناجين، سعة كل منها 60 مل. غير مناسبة للمايكروويف."],
                'price' => '110.00', 'stock' => [20], 'weight' => 600,
                'categories' => ['coffee-tea'], 'images' => [$white],
            ],
            [
                'name' => ['en' => 'Date Serving Platter', 'ar' => 'طبق تقديم التمر'], 'sku' => 'SH-SRV-DATE', 'shape' => 'platter',
                'short' => ['en' => 'A shallow platter for dates, sweets or bakhoor.', 'ar' => 'طبق مسطح للتمر أو الحلويات أو البخور.'],
                'description' => ['en' => "32 cm across. Olive wood is oiled by hand; marble is sealed.", 'ar' => "قطره 32 سم. خشب الزيتون مزيّت يدوياً، والرخام معالج ضد البقع."],
                'options' => [['Material', 'الخامة', [['Olive wood', 'خشب الزيتون'], ['Marble', 'رخام']]]],
                'price' => ['175.00', '210.00'], 'stock' => [6, 3], 'weight' => 1500,
                'categories' => ['serveware'], 'images' => [$olive], 'image_option' => 0,
            ],
            [
                'name' => ['en' => 'Hand-painted Serving Bowl', 'ar' => 'وعاء تقديم مرسوم يدوياً'], 'sku' => 'SH-SRV-BOWL', 'shape' => 'bowl',
                'short' => ['en' => 'Stoneware bowl, painted by hand.', 'ar' => 'وعاء من الخزف مرسوم يدوياً.'],
                'description' => ['en' => "25 cm across. Dishwasher safe.", 'ar' => "قطره 25 سم. مناسب لغسالة الأطباق."],
                'price' => '130.00', 'stock' => [10], 'weight' => 900, 'status' => 'draft',
                'categories' => ['serveware'], 'images' => [$indigo],
            ],
            [
                'name' => ['en' => 'Majlis Gift Box', 'ar' => 'صندوق هدايا المجلس'], 'sku' => 'SH-GFT-MAJL', 'shape' => 'box',
                'short' => ['en' => 'Oud candle, gahwa cups and dates in a woven-wrapped box.', 'ar' => 'شمعة عود وفناجين قهوة وتمر في صندوق مغلف بنسيج يدوي.'],
                'description' => ['en' => "Includes a 200 g Oud & Amber candle, six gahwa cups and 500 g of Khalas dates.\n\nAdd a gift message at checkout.", 'ar' => "يحتوي على شمعة عود وعنبر 200 غ وستة فناجين قهوة و500 غ من تمر الخلاص.\n\nأضف رسالة إهداء عند إتمام الطلب."],
                'price' => '450.00', 'stock' => [5], 'weight' => 2500, 'featured' => true,
                'categories' => ['gifts'], 'images' => [$red, $brass],
            ],
            [
                'name' => ['en' => 'Mini Mabkhara', 'ar' => 'مبخرة صغيرة'], 'sku' => 'SH-GFT-MABK', 'shape' => 'mabkhara',
                'short' => ['en' => 'A small incense burner for bakhoor.', 'ar' => 'مبخرة صغيرة للبخور.'],
                'description' => ['en' => "Brass-finished metal, 14 cm tall.", 'ar' => "معدن بلمسة نحاسية، ارتفاعها 14 سم."],
                'price' => '90.00', 'stock' => [3], 'weight' => 400,
                'categories' => ['gifts', 'candles'], 'images' => [$brass],
            ],
        ];
    }
}
