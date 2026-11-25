<?php

namespace Tests\Feature\Furnivo;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;

class FurnivoCartDrawerTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function variantId(string $slug): int
    {
        return Product::query()->where('slug', $slug)->firstOrFail()->variants()->value('id');
    }

    /** One guest bag across requests (PHPUnit keeps no cookies; JSON requests need credentials). */
    private function guest()
    {
        $token = Cart::query()->value('token');

        return $token ? $this->withCredentials()->withCookie(CartService::COOKIE, $token) : $this;
    }

    public function test_empty_drawer(): void
    {
        $this->get('/cart/drawer')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('Your cart · <span>0</span>', false)
            ->assertSee('Your cart is empty.')->assertSee('Continue browsing')
            ->assertDontSee('Checkout');
        $this->assertStringContainsString('no-store', $this->get('/cart/drawer')->headers->get('Cache-Control'));
    }

    public function test_drawer_lists_lines_with_brand_choices_and_subtotal(): void
    {
        $this->guest()->postJson('/cart/items', ['variant_id' => $this->variantId('nord-accent'), 'quantity' => 2, 'options' => ['Colour' => 'Ivory']])->assertOk();
        $this->guest()->postJson('/cart/items', ['variant_id' => $this->variantId('lucca-sofa'), 'quantity' => 1])->assertOk();

        $this->guest()->get('/cart/drawer')->assertOk()->assertSeeInOrder([
            'Your cart · <span>3</span>',
            'Loom Collection', 'Nord Accent Chair', 'Colour: Ivory', 'Remove', '8,400',
            'Al Huzaifa Furniture', 'Lucca 3-Seater Sofa', 'Remove', '18,400',
            'Subtotal', '26,800', 'Shipping &amp; VAT calculated at checkout.', 'Checkout', 'View cart',
        ], false);
    }

    public function test_quantity_and_remove_from_the_drawer(): void
    {
        $this->guest()->postJson('/cart/items', ['variant_id' => $this->variantId('nord-accent'), 'quantity' => 1])->assertOk();
        $item = CartItem::query()->sole();

        // The drawer sends POST + _method, as a form would.
        $this->guest()->postJson("/cart/items/{$item->id}", ['_method' => 'PATCH', 'quantity' => 3])->assertOk()->assertJson(['count' => 3]);
        $this->assertSame(3, $item->fresh()->quantity);

        $this->guest()->postJson("/cart/items/{$item->id}", ['_method' => 'DELETE'])->assertOk()->assertJson(['count' => 0]);
        $this->assertNull($item->fresh());
    }

    public function test_another_visitors_line_cannot_be_changed(): void
    {
        $this->postJson('/cart/items', ['variant_id' => $this->variantId('nord-accent'), 'quantity' => 1])->assertOk();
        $item = CartItem::query()->sole();

        // A different visitor (no bag cookie) tries to change it.
        $this->postJson("/cart/items/{$item->id}", ['_method' => 'PATCH', 'quantity' => 5])->assertStatus(404);
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_layout_has_the_drawer_and_the_bag_icon_opens_it(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('cartDrawer(', false)
            ->assertSee("\$dispatch('open-cart-drawer')", false);
    }
}
