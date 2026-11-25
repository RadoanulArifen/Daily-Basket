<?php

namespace Tests\Feature\Furnivo;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;

class FurnivoCartPageTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function guest()
    {
        $token = Cart::query()->value('token');

        return $token ? $this->withCredentials()->withCookie(CartService::COOKIE, $token) : $this;
    }

    private function add(string $slug, int $quantity = 1, array $options = []): void
    {
        $variant = Product::query()->where('slug', $slug)->firstOrFail()->variants()->value('id');
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant, 'quantity' => $quantity, 'options' => $options])->assertOk();
    }

    public function test_empty_bag_follows_the_design(): void
    {
        $this->get('/cart')->assertOk()
            ->assertSeeInOrder(['Home', 'Cart', 'Your cart', 'Your cart is empty.', 'Continue browsing'], false)
            ->assertDontSee('Order summary');
    }

    public function test_bag_lists_the_lines_and_a_summary(): void
    {
        $this->add('nord-accent', 1, ['Colour' => 'Ivory']);
        $this->add('lucca-sofa');

        $this->guest()->get('/cart')->assertOk()->assertSeeInOrder([
            'Your cart',
            'Loom Collection', 'Nord Accent Chair', 'Wool, powder-coated steel', 'Colour: Ivory', 'Remove', '4,200',
            'Al Huzaifa Furniture', 'Lucca 3-Seater Sofa', '18,400',
            'Order summary', 'Subtotal', '22,600', 'Delivery', 'Calculated at checkout', 'Total', '22,600',
            'Discount code', 'Checkout', 'Guest checkout · no account required',
        ], false);
    }

    public function test_quantity_and_remove_work_from_the_bag(): void
    {
        $this->add('nord-accent');
        $item = CartItem::query()->sole();

        $this->guest()->patch("/cart/items/{$item->id}", ['quantity' => 3])->assertRedirect('/cart');
        $this->assertSame(3, $item->fresh()->quantity);
        $this->guest()->get('/cart')->assertOk()->assertSee('12,600');   // 3 × 4,200

        $this->guest()->delete("/cart/items/{$item->id}")->assertRedirect('/cart');
        $this->guest()->get('/cart')->assertOk()->assertSee('Your cart is empty.');
    }

    public function test_a_discount_code_shows_on_the_bag(): void
    {
        \App\Models\Discount::query()->create([
            'code' => 'FURNIVO10', 'type' => 'percentage', 'value' => 10,
            'is_active' => true, 'starts_at' => null, 'ends_at' => null,
        ]);

        $this->add('nord-accent');
        $this->guest()->post('/cart/discount', ['code' => 'FURNIVO10'])->assertRedirect();

        $this->guest()->get('/cart')->assertOk()->assertSee('FURNIVO10')->assertSee('3,780');   // 4,200 less 10%
    }
}
