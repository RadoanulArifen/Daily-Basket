<?php

namespace Tests\Feature\Freshnest;

use App\Models\Product;

/** With Arabic on, /ar/ pages are right-to-left and in Arabic: the theme's words and the demo content. */
class FreshnestArabicTest extends FreshnestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['store.all_locales' => ['en' => ['name' => 'English', 'dir' => 'ltr'], 'ar' => ['name' => 'العربية', 'dir' => 'rtl']]]);
        $this->seedFreshnest();
        settings()->setMany('general', ['locales' => ['en', 'ar']]);
    }

    public function test_home_page_is_in_arabic(): void
    {
        $this->get('/ar')->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('تسوّق حسب الفئة')          // theme word: Shop by Category
            ->assertSee('منتجات عضوية فاخرة')       // hero banner in Arabic
            ->assertSee('فواكه')                    // category
            ->assertSee('أفوكادو هاس عضوي')          // product
            ->assertSee('من المزرعة إليك خلال 45 دقيقة') // footer tagline
            ->assertDontSee("Today's Fresh Picks");
    }

    public function test_product_shop_and_pages_are_in_arabic(): void
    {
        $this->get('/ar/products/free-range-eggs')->assertOk()->assertSee('بيض دجاج حر')->assertSee('التخزين والاستخدام')->assertSee('لكل 100 غ')->assertSee('حجم العبوة');
        $this->get('/ar/shop')->assertOk()->assertSee('ترتيب حسب');
        $this->get('/ar/pages/faq')->assertOk()->assertSee('ما سرعة التوصيل؟');
        $this->get('/ar/pages/delivery-info')->assertOk()->assertSee('مناطق التوصيل')->assertSee('متوسط وقت التوصيل');
    }

    public function test_english_is_unchanged(): void
    {
        $this->get('/')->assertOk()->assertSee('Shop by Category')->assertSee('Organic Hass Avocado')->assertDontSee('أفوكادو');
        $this->assertSame('Organic Hass Avocado', Product::query()->where('slug', 'organic-hass-avocado')->first()->getTranslation('name', 'en'));
    }

    public function test_arabic_typed_in_the_admin_is_kept(): void
    {
        $p = Product::query()->where('slug', 'rainbow-chard')->firstOrFail();
        $p->setTranslation('name', 'ar', 'سلق خاص')->saveQuietly();
        $this->seed(\Database\Seeders\FreshnestHomepageSeeder::class);
        $this->assertSame('سلق خاص', $p->fresh()->getTranslation('name', 'ar'));
    }
}
