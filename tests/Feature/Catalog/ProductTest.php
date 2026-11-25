<?php

namespace Tests\Feature\Catalog;

use App\Enums\InventoryReason;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

class ProductTest extends CatalogTestCase
{
    public function test_product_pages_load(): void
    {
        $this->get('/admin/products')->assertOk()->assertSee('Add your first product');
        $this->get('/admin/products/create')->assertOk();
    }

    public function test_a_simple_product_is_created_with_one_default_variant(): void
    {
        $category = Category::create(['name' => ['en' => 'Cushions'], 'slug' => 'cushions']);

        $response = $this->post('/admin/products', $this->productPayload(['category_ids' => [$category->id]]));

        $product = Product::firstOrFail();
        $response->assertRedirect("/admin/products/{$product->id}/edit")->assertSessionHasNoErrors();

        $this->assertSame('woven-cushion', $product->slug);
        $this->assertSame('وسادة منسوجة', $product->getTranslation('name', 'ar'));
        $this->assertNotNull($product->published_at);
        $this->assertTrue($product->categories->contains($category));

        $variant = $product->variants()->sole();
        $this->assertSame('145.00', $variant->price->toDecimal());
        $this->assertSame(10, $variant->stock_quantity);
        $this->assertSame(0, $variant->optionValues()->count());

        // Starting stock is logged.
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'change' => 10, 'reason' => InventoryReason::Initial->value, 'admin_id' => $this->admin->id]);
    }

    public function test_slugs_stay_unique(): void
    {
        $this->post('/admin/products', $this->productPayload(['variants' => [['sku' => 'A']]]));
        $this->post('/admin/products', $this->productPayload(['variants' => [['sku' => 'B']]]));

        $this->assertSame(['woven-cushion', 'woven-cushion-2'], Product::orderBy('id')->pluck('slug')->all());
    }

    public function test_options_create_one_variant_per_combination(): void
    {
        $this->post('/admin/products', $this->variantPayload())->assertSessionHasNoErrors();

        $product = Product::with(['options.values', 'variants.optionValues'])->firstOrFail();
        $this->assertCount(2, $product->options);
        $this->assertCount(4, $product->variants);
        $this->assertSame('Blue / Large', $product->variants[3]->title('en'));
        $this->assertSame('185.00', $product->variants[3]->price->toDecimal());
        $this->assertSame(10, $product->totalStock()); // 1 + 2 + 3 + 4
        [$min, $max] = $product->priceRange();
        $this->assertSame(['145.00', '185.00'], [$min->toDecimal(), $max->toDecimal()]);
    }

    public function test_editing_keeps_variants_and_applies_only_the_stock_difference(): void
    {
        $this->post('/admin/products', $this->productPayload());
        $product = Product::firstOrFail();
        $variant = $product->variants()->sole();

        // A sale happens while the admin has the form open (10 -> 8).
        app(\App\Services\Inventory\InventoryService::class)->adjust($variant, -2, InventoryReason::Correction);

        // The admin loaded the form when stock was 10 and types 15 (+5).
        $payload = $this->productPayload(['variants' => [['id' => $variant->id, 'stock_quantity' => '15', 'original_stock' => '10', 'price' => '150.00']]]);
        $this->put("/admin/products/{$product->id}", $payload)->assertSessionHasNoErrors();

        $variant->refresh();
        $this->assertSame(13, $variant->stock_quantity); // 8 + 5, the sale isn't lost
        $this->assertSame('150.00', $variant->price->toDecimal());
        $this->assertSame(1, $product->variants()->count());
    }

    public function test_removed_variants_are_soft_deleted(): void
    {
        $this->post('/admin/products', $this->variantPayload());
        $product = Product::firstOrFail();

        $payload = $this->variantPayload();
        $existing = $product->variants()->with('optionValues')->get();
        // Keep only the red ones; submit existing ids and value ids.
        $payload['options'][0]['values'] = [['id' => $existing[0]->optionValues[0]->id, 'key' => 'red', 'value' => ['en' => 'Red']]];
        $payload['variants'] = [
            array_merge($payload['variants'][0], ['id' => $existing[0]->id]),
            array_merge($payload['variants'][1], ['id' => $existing[1]->id]),
        ];

        $this->put("/admin/products/{$product->id}", $payload)->assertSessionHasNoErrors();

        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(2, ProductVariant::onlyTrashed()->count());
        $this->assertSame(1, $product->options()->first()->values()->count());
    }

    public function test_removing_all_options_leaves_one_default_variant(): void
    {
        $this->post('/admin/products', $this->variantPayload());
        $product = Product::firstOrFail();
        $first = $product->variants()->first();

        $this->put("/admin/products/{$product->id}", $this->productPayload(['variants' => [['id' => $first->id, 'sku' => 'SH-1']]]))->assertSessionHasNoErrors();

        $this->assertSame(0, $product->options()->count());
        $this->assertSame($first->id, $product->variants()->sole()->id);
        $this->assertSame(0, $product->variants()->sole()->optionValues()->count());
    }

    public function test_validation_rules(): void
    {
        // Compare-at price must be higher than the price.
        $this->post('/admin/products', $this->productPayload(['variants' => [['compare_at_price' => '100.00']]]))
            ->assertSessionHasErrors('variants.0.compare_at_price');

        // Money is never accepted with more than two decimals.
        $this->post('/admin/products', $this->productPayload(['variants' => [['price' => '10.999']]]))
            ->assertSessionHasErrors('variants.0.price');

        // English name required.
        $this->post('/admin/products', $this->productPayload(['name' => ['en' => '']]))
            ->assertSessionHasErrors('name.en');

        // Duplicate combination.
        $payload = $this->variantPayload();
        $payload['variants'][1]['option_keys'] = ['red', 'sm'];
        $this->post('/admin/products', $payload)->assertSessionHasErrors('variants.1');

        // A variant missing one of the options.
        $payload = $this->variantPayload();
        $payload['variants'][0]['option_keys'] = ['red'];
        $this->post('/admin/products', $payload)->assertSessionHasErrors('variants.0');

        // Duplicate option values.
        $payload = $this->variantPayload();
        $payload['options'][1]['values'][1]['value']['en'] = 'small';
        $this->post('/admin/products', $payload)->assertSessionHasErrors('options.1.values');

        $this->assertSame(0, Product::count());
    }

    public function test_sku_must_be_unique_across_products(): void
    {
        $this->post('/admin/products', $this->productPayload())->assertSessionHasNoErrors();

        $this->post('/admin/products', $this->productPayload(['name' => ['en' => 'Other']]))
            ->assertSessionHasErrors('variants.0.sku');

        // Saving the same product again with its own SKU is fine.
        $product = Product::firstOrFail();
        $this->put("/admin/products/{$product->id}", $this->productPayload(['variants' => [['id' => $product->variants()->value('id')]]]))
            ->assertSessionHasNoErrors();
    }

    public function test_a_variant_id_from_another_product_cannot_be_hijacked(): void
    {
        $this->post('/admin/products', $this->productPayload());
        $victim = ProductVariant::firstOrFail();

        $this->post('/admin/products', $this->productPayload(['name' => ['en' => 'Attacker'], 'variants' => [['id' => $victim->id, 'sku' => 'X', 'price' => '1.00']]]))
            ->assertSessionHasNoErrors();

        $this->assertSame('145.00', $victim->fresh()->price->toDecimal());
        $this->assertSame(2, ProductVariant::count());
    }

    public function test_list_search_filters_and_bulk_actions(): void
    {
        $this->post('/admin/products', $this->productPayload());
        $this->post('/admin/products', $this->productPayload(['name' => ['en' => 'Brass Dallah', 'ar' => 'دلة'], 'status' => 'draft', 'variants' => [['sku' => 'DAL-1']]]));

        $this->get('/admin/products?q=dallah')->assertSee('Brass Dallah')->assertDontSee('Woven Cushion');
        $this->get('/admin/products?q=dal-1')->assertSee('Brass Dallah');
        $this->get('/admin/products?q='.urlencode('دلة'))->assertSee('Brass Dallah');
        $this->get('/admin/products?status=draft')->assertSee('Brass Dallah')->assertDontSee('Woven Cushion');

        $ids = Product::pluck('id')->all();
        $this->post('/admin/products/bulk', ['action' => 'archived', 'ids' => $ids])->assertSessionHas('success');
        $this->assertSame(2, Product::where('status', 'archived')->count());

        $this->post('/admin/products/bulk', ['action' => 'delete', 'ids' => $ids]);
        $this->assertSame(0, Product::count());
        $this->assertSame(2, Product::onlyTrashed()->count());
    }

    public function test_edit_page_loads_and_old_input_survives_errors(): void
    {
        $this->post('/admin/products', $this->variantPayload());
        $product = Product::firstOrFail();

        $this->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('Woven Cushion');

        $payload = $this->variantPayload();
        $payload['variants'][0]['price'] = '';
        $this->from("/admin/products/{$product->id}/edit")->put("/admin/products/{$product->id}", $payload)
            ->assertRedirect("/admin/products/{$product->id}/edit");
        $this->get("/admin/products/{$product->id}/edit")->assertOk();
    }
}
