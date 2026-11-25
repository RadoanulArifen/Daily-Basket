<?php

namespace Tests\Feature\Furnivo;

use App\Models\Product;

class FurnivoProductVariantsTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_a_product_with_several_variants_opens(): void
    {
        $product = Product::query()->where('slug', 'halo-floor')->firstOrFail();
        $first = $product->variants()->firstOrFail();

        foreach (['M', 'L'] as $i => $size) {
            $variant = $first->replicate(['sku']);
            $variant->sku = $first->sku.'-'.$size;
            $variant->position = $i + 2;
            $variant->save();
        }

        $this->assertSame(3, $product->variants()->count());

        // Options used to break the page: each variant checked its product, which was not
        // loaded, and lazy loading is off.
        $this->get("/products/{$product->slug}")->assertOk()->assertSee('Add to cart', false);
    }

    /** A product with real options (Size: S, M, L) shows them and adds the chosen one. */
    private function withSizes(): \App\Models\Product
    {
        $product = Product::query()->where('slug', 'halo-floor')->firstOrFail();
        $first = $product->variants()->firstOrFail();
        $option = $product->options()->create(['name' => ['en' => 'Size'], 'position' => 0]);

        $values = collect(['S', 'M', 'L'])->map(fn ($label, $i) => $option->values()->create(['value' => ['en' => $label], 'position' => $i]));
        $first->optionValues()->sync([$values[0]->id]);

        foreach ([1, 2] as $i) {
            $variant = $first->replicate(['sku']);
            $variant->sku = $first->sku.'-'.$values[$i]->value;
            $variant->position = $i + 1;
            $variant->save();
            $variant->optionValues()->sync([$values[$i]->id]);
        }

        return $product->fresh(['options.values', 'variants.optionValues']);
    }

    public function test_the_options_are_shown_on_the_product_page(): void
    {
        $product = $this->withSizes();

        $page = $this->get("/products/{$product->slug}")->assertOk();
        $page->assertSee('Size')->assertSee('>S<', false)->assertSee('>M<', false)->assertSee('>L<', false);
        $page->assertSee('variantPicker(', false);
    }

    public function test_the_chosen_option_is_what_goes_in_the_bag(): void
    {
        $product = $this->withSizes();
        $medium = $product->variants->first(fn ($v) => $v->optionValues->first()?->value === 'M');

        $this->post('/cart/items', ['variant_id' => $medium->id, 'quantity' => 1])->assertRedirect();

        $item = \App\Models\CartItem::query()->latest('id')->firstOrFail();
        $this->assertSame($medium->id, $item->product_variant_id);
    }
}
