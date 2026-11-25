<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CatalogTestCase extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCurrencies();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    /** A valid product form submission; override any part. */
    protected function productPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => ['en' => 'Woven Cushion', 'ar' => 'وسادة منسوجة'],
            'short_description' => ['en' => 'Woven by hand.'],
            'description' => ['en' => 'Wool front.'],
            'status' => 'active',
            'is_featured' => '0',
            'category_ids' => [],
            'media_ids' => [],
            'variants' => [[
                'price' => '145.00',
                'compare_at_price' => '',
                'sku' => 'SH-1',
                'track_inventory' => '1',
                'allow_backorder' => '0',
                'is_active' => '1',
                'stock_quantity' => '10',
            ]],
        ], $overrides);
    }

    /** Two options (Colour × Size) = four variants. */
    protected function variantPayload(): array
    {
        $payload = $this->productPayload();
        $payload['options'] = [
            ['key' => 'c', 'name' => ['en' => 'Colour', 'ar' => 'اللون'], 'values' => [
                ['key' => 'red', 'value' => ['en' => 'Red', 'ar' => 'أحمر']],
                ['key' => 'blue', 'value' => ['en' => 'Blue']],
            ]],
            ['key' => 's', 'name' => ['en' => 'Size'], 'values' => [
                ['key' => 'sm', 'value' => ['en' => 'Small']],
                ['key' => 'lg', 'value' => ['en' => 'Large']],
            ]],
        ];
        $payload['variants'] = [];
        foreach ([['red', 'sm'], ['red', 'lg'], ['blue', 'sm'], ['blue', 'lg']] as $i => $keys) {
            $payload['variants'][] = [
                'option_keys' => $keys,
                'price' => $keys[1] === 'lg' ? '185.00' : '145.00',
                'sku' => 'SH-'.($i + 1),
                'track_inventory' => '1', 'allow_backorder' => '0', 'is_active' => '1',
                'stock_quantity' => (string) ($i + 1),
            ];
        }

        return $payload;
    }
}
