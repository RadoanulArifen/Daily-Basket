<?php

namespace Tests\Feature\Storefront;

use App\Actions\Catalog\SaveProduct;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StorefrontTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCurrencies();
        $this->seed(SettingsSeeder::class);
    }

    /**
     * Create a product through the same action the admin uses.
     *
     * @param  list<array>  $variants  override per variant
     */
    protected function makeProduct(string $name, array $attributes = [], array $variants = [[]], array $options = []): Product
    {
        $defaults = ['price' => '100.00', 'compare_at_price' => null, 'track_inventory' => true, 'allow_backorder' => false, 'is_active' => true, 'stock_quantity' => 10];

        return app(SaveProduct::class)->handle([
            'name' => ['en' => $name, 'ar' => $attributes['ar'] ?? null],
            'status' => $attributes['status'] ?? 'active',
            'is_featured' => $attributes['featured'] ?? false,
            'short_description' => ['en' => $attributes['short'] ?? null],
            'category_ids' => $attributes['categories'] ?? [],
            'media_ids' => [],
            'options' => $options,
            'variants' => array_map(fn ($v) => array_merge($defaults, $v), $variants),
        ]);
    }

    protected function category(string $slug, ?Category $parent = null, bool $active = true): Category
    {
        return Category::create(['name' => ['en' => ucfirst($slug), 'ar' => 'قسم'], 'slug' => $slug, 'parent_id' => $parent?->id, 'is_active' => $active]);
    }

    /** Two sizes: Small (in stock) and Large (sold out). */
    protected function sizedProduct(string $name = 'Woven Cushion'): Product
    {
        return $this->makeProduct($name, [], [
            ['option_keys' => ['s'], 'price' => '145.00', 'stock_quantity' => 3],
            ['option_keys' => ['l'], 'price' => '185.00', 'stock_quantity' => 0],
        ], [
            ['key' => 'size', 'name' => ['en' => 'Size', 'ar' => 'المقاس'], 'values' => [
                ['key' => 's', 'value' => ['en' => 'Small', 'ar' => 'صغير']],
                ['key' => 'l', 'value' => ['en' => 'Large', 'ar' => 'كبير']],
            ]],
        ]);
    }
}
