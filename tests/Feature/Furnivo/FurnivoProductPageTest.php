<?php

namespace Tests\Feature\Furnivo;

use App\Models\CartItem;
use App\Models\Product;

class FurnivoProductPageTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    /** Adds to the same guest bag across requests (PHPUnit does not keep cookies). */
    private function add(int $variantId, int $quantity, array $options)
    {
        $token = \App\Models\Cart::query()->value('token');
        // JSON test requests only send cookies with credentials (a browser always sends them).
        $request = $token ? $this->withCredentials()->withCookie(\App\Services\Cart\CartService::COOKIE, $token) : $this;

        return $request->postJson('/cart/items', ['variant_id' => $variantId, 'quantity' => $quantity, 'options' => $options]);
    }

    private function nord(): Product
    {
        return Product::query()->where('slug', 'nord-accent')->with('variants')->firstOrFail();
    }

    public function test_product_page_shows_the_design_content(): void
    {
        $this->get('/products/nord-accent')->assertOk()->assertSeeInOrder([
            'Home', 'Seating', 'Nord Accent Chair',
            'Nord Accent Chair', '4.1', 'reviews', '$', '4,200', 'incl. VAT',
            \App\Support\RichText::text(\App\Models\Product::where('slug', 'nord-accent')->first()->short_description),
            'Configure your piece', 'Size guide', 'Colour', 'Walnut', 'Charcoal', 'Dimensions', '+ Custom size', 'Frame', 'Upholstery', 'Configuration',
            'Add to cart', 'Custom Order — Make it yours', '6–12 week', 'White-glove', '5-year',
            'Description', 'Crafted by Loom Collection', 'Dimensions &amp; Materials', 'Delivery &amp; Returns', 'Product Code', 'LOO-1004',
            'Often bought together', 'Add selected to cart',
            'Ratings &amp; reviews', 'Write a review', 'Layla A.', 'Verified', 'Load more reviews (6)',
            'You may also like',
        ], false);
    }

    public function test_recently_viewed_products_appear_after_browsing(): void
    {
        // As in the design, the row is filled from the catalogue until something has been viewed.
        $this->get('/products/monolith-table')->assertOk()->assertSeeInOrder([__('store.theme.recently_viewed'), 'Lucca 3-Seater Sofa']);
        $this->get('/products/halden-lounge')->assertOk()->assertSeeInOrder([__('store.theme.recently_viewed'), 'Monolith Dining Table']);
    }

    public function test_configuration_choices_travel_with_the_bag_line(): void
    {
        $variant = $this->nord()->variants->first();

        $this->add($variant->id, 1, ['Colour' => 'Ivory', 'Frame' => 'Oak'])->assertOk();
        $this->add($variant->id, 1, ['Colour' => 'Ivory', 'Frame' => 'Oak'])->assertOk();
        $this->add($variant->id, 1, ['Colour' => 'Forest', 'Frame' => 'Oak'])->assertOk();

        $lines = CartItem::query()->orderBy('id')->get();
        $this->assertCount(2, $lines, 'A different configuration is a separate line.');
        $this->assertSame(2, $lines[0]->quantity);
        $this->assertSame('Colour: Ivory · Frame: Oak', $lines[0]->optionsLabel());

        $this->withCookie(\App\Services\Cart\CartService::COOKIE, \App\Models\Cart::query()->value('token'))->get('/cart')->assertOk()->assertSee('Colour: Ivory · Frame: Oak')->assertSee('Colour: Forest · Frame: Oak');
    }

    public function test_choices_a_product_does_not_offer_are_dropped(): void
    {
        $variant = $this->nord()->variants->first();

        $this->add($variant->id, 1, ['Colour' => 'Hot pink', 'Frame' => 'Oak', 'Evil' => '<script>'])->assertOk();

        $this->assertSame(['Frame' => 'Oak'], CartItem::query()->first()->options);
    }

    public function test_stock_is_shared_between_configurations(): void
    {
        $variant = $this->nord()->variants->first();
        $variant->forceFill(['track_inventory' => true, 'allow_backorder' => false, 'stock_quantity' => 3])->save();

        $this->add($variant->id, 2, ['Frame' => 'Oak'])->assertOk();
        $this->add($variant->id, 2, ['Frame' => 'Ash'])->assertStatus(422);
        $this->add($variant->id, 1, ['Frame' => 'Ash'])->assertOk();
    }
}
