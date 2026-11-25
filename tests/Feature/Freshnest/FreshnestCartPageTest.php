<?php

namespace Tests\Feature\Freshnest;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartService;

/** The cart page from the design, with the store's real cart. */
class FreshnestCartPageTest extends FreshnestTestCase
{
    private function guest()
    {
        $token = Cart::query()->value('token');

        return $token ? $this->withCredentials()->withCookie(CartService::COOKIE, $token) : $this;
    }

    public function test_empty_cart(): void
    {
        $this->seedFreshnest();

        $this->get('/cart')->assertOk()->assertSee('Your basket')->assertSee('Your basket is empty.')->assertSee('0 items');
    }

    public function test_lines_summary_and_quantity_controls(): void
    {
        $this->seedFreshnest();
        $variant = Product::query()->where('slug', 'baby-leaf-spinach')->firstOrFail()->variants()->orderBy('position')->firstOrFail();
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->guest()->get('/cart')->assertOk()
            ->assertSee('2 items')->assertSee('Baby Leaf Spinach')->assertSee('250 g bag · $2.10', false)
            ->assertSee('Add $45.80 more for free delivery', false)
            ->assertSee('$4.99')->assertSee('$9.19')->assertSee('Checkout')
            ->assertSee('name="_method" value="PATCH"', false)->assertSee('name="_method" value="DELETE"', false);
    }
}
