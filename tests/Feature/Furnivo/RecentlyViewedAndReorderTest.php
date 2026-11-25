<?php

namespace Tests\Feature\Furnivo;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Catalog\RecentlyViewed;

class RecentlyViewedAndReorderTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_pieces_you_looked_at_come_back_newest_first(): void
    {
        $slugs = Product::query()->where('status', 'active')->limit(3)->pluck('slug');

        foreach ($slugs as $slug) {
            $this->get("/products/{$slug}")->assertOk();
        }

        // Opening the last one again shows the ones before it.
        $page = $this->get('/products/'.$slugs->last())->assertOk();

        $page->assertSee(__('store.theme.recently_viewed'));
        $page->assertSee(Product::query()->where('slug', $slugs[1])->value('slug'), false);
    }

    public function test_the_piece_you_are_reading_is_not_in_its_own_rail(): void
    {
        $product = Product::query()->where('status', 'active')->firstOrFail();

        $seen = app(RecentlyViewed::class);
        $this->assertTrue($seen->products(except: $product)->doesntContain(fn ($p) => $p->id === $product->id));
    }

    public function test_reordering_puts_the_pieces_back_in_the_bag(): void
    {
        $customer = User::factory()->create();
        $variant = ProductVariant::query()->firstOrFail();
        $variant->forceFill(['stock_quantity' => 10, 'track_inventory' => true])->save();

        // An order for this customer, made the normal way.
        $this->actingAs($customer, 'web');
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertRedirect();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => $customer->email, 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = \App\Models\Order::query()->latest('id')->firstOrFail();
        $this->assertSame($customer->id, $order->user_id);

        $this->post("/account/orders/{$order->id}/reorder")->assertRedirect()->assertSessionHas('success');

        $this->assertSame(2, (int) \App\Models\CartItem::query()->latest('id')->value('quantity'));
    }

    public function test_you_cannot_reorder_somebody_elses_order(): void
    {
        // One customer places an order the normal way…
        $theirs = User::factory()->create();
        $variant = ProductVariant::query()->firstOrFail();
        $variant->forceFill(['stock_quantity' => 10, 'track_inventory' => true])->save();

        $this->actingAs($theirs, 'web');
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => $theirs->email, 'name' => 'Other Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '9 Other Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ]);
        $order = \App\Models\Order::query()->latest('id')->firstOrFail();

        // …and somebody else cannot reorder it, even knowing the id.
        $mine = User::factory()->create();

        $this->actingAs($mine, 'web')
            ->post("/account/orders/{$order->id}/reorder")
            ->assertNotFound();
    }
}
