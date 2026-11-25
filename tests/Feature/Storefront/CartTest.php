<?php

namespace Tests\Feature\Storefront;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;

class CartTest extends StorefrontTestCase
{
    private function variantOf(string $name): ProductVariant
    {
        return $this->makeProduct($name)->variants()->firstOrFail();
    }

    private function cartCookie(): string
    {
        return Cart::query()->whereNull('user_id')->latest('id')->value('token');
    }

    public function test_a_guest_can_add_update_and_remove_items(): void
    {
        $variant = $this->variantOf('Oud Candle');

        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('store_toast')
            ->assertCookie(CartService::COOKIE);

        $token = $this->cartCookie();
        $item = CartItem::sole();
        $this->assertSame(2, $item->quantity);

        // Adding again adds to the same line.
        $this->withCookie(CartService::COOKIE, $token)->post('/cart/items', ['variant_id' => $variant->id]);
        $this->assertSame(3, $item->fresh()->quantity);

        $this->withCookie(CartService::COOKIE, $token)->get('/cart')->assertOk()
            ->assertSee('Oud Candle')
            ->assertSee("AED\u{00A0}300.00")
            ->assertSee('Subtotal (3 items)');

        $this->withCookie(CartService::COOKIE, $token)->patch("/cart/items/{$item->id}", ['quantity' => 1])->assertRedirect('/cart');
        $this->assertSame(1, $item->fresh()->quantity);

        $this->withCookie(CartService::COOKIE, $token)->delete("/cart/items/{$item->id}")->assertRedirect('/cart');
        $this->assertSame(0, CartItem::count());
    }

    public function test_json_add_returns_the_new_count(): void
    {
        $variant = $this->variantOf('Oud Candle');

        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 4])
            ->assertOk()
            ->assertJson(['count' => 4]);
    }

    public function test_stock_limits_are_enforced(): void
    {
        $variant = $this->variantOf('Oud Candle'); // 10 in stock

        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 11])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');

        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 10])->assertOk();
        $token = $this->cartCookie();

        // JSON requests carry cookies like a same-origin fetch() does.
        $this->withCredentials()->withCookie(CartService::COOKIE, $token)->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonFragment(['You already have all 10 available in your bag.']);
    }

    public function test_sold_out_hidden_and_draft_items_cannot_be_added(): void
    {
        $soldOut = $this->makeProduct('Sold Out', [], [['stock_quantity' => 0]])->variants()->first();
        $draft = $this->makeProduct('Draft', ['status' => 'draft'])->variants()->first();
        $backorder = $this->makeProduct('Backorder', [], [['stock_quantity' => 0, 'allow_backorder' => true]])->variants()->first();

        $this->postJson('/cart/items', ['variant_id' => $soldOut->id])->assertUnprocessable();
        $this->postJson('/cart/items', ['variant_id' => $draft->id])->assertUnprocessable();
        $this->postJson('/cart/items', ['variant_id' => 999999])->assertUnprocessable();
        $this->postJson('/cart/items', ['variant_id' => $backorder->id, 'quantity' => 5])->assertOk();
    }

    public function test_the_price_always_comes_from_the_database(): void
    {
        $variant = $this->variantOf('Oud Candle');

        // Extra fields a tampered form might send are ignored.
        $this->post('/cart/items', ['variant_id' => $variant->id, 'price' => '0.01', 'quantity' => 1]);
        $token = $this->cartCookie();

        $this->withCookie(CartService::COOKIE, $token)->get('/cart')->assertSee("AED\u{00A0}100.00")->assertDontSee('0.01');

        // A price change is reflected immediately.
        $variant->update(['price' => '120.00']);
        $this->withCookie(CartService::COOKIE, $token)->get('/cart')->assertSee("AED\u{00A0}120.00");
    }

    public function test_cart_reconciles_items_that_changed_since_they_were_added(): void
    {
        $shrinking = $this->variantOf('Shrinking Stock');
        $vanishing = $this->variantOf('Vanishing Product');

        $this->post('/cart/items', ['variant_id' => $shrinking->id, 'quantity' => 5]);
        $token = $this->cartCookie();
        $this->withCookie(CartService::COOKIE, $token)->post('/cart/items', ['variant_id' => $vanishing->id]);

        $shrinking->forceFill(['stock_quantity' => 2])->save();
        $vanishing->product->update(['status' => 'draft']);

        $this->withCookie(CartService::COOKIE, $token)->get('/cart')
            ->assertSee('Only 2 of “Shrinking Stock” left')
            ->assertSee('“Vanishing Product” is no longer available');

        $this->assertSame(2, CartItem::where('product_variant_id', $shrinking->id)->value('quantity'));
        $this->assertFalse(CartItem::where('product_variant_id', $vanishing->id)->exists());
    }

    public function test_someone_elses_cart_item_cannot_be_changed(): void
    {
        $variant = $this->variantOf('Oud Candle');
        $this->post('/cart/items', ['variant_id' => $variant->id]);
        $item = CartItem::sole();

        // A different visitor (no cookie) tries to change it.
        $this->patch("/cart/items/{$item->id}", ['quantity' => 5])->assertNotFound();
        $this->delete("/cart/items/{$item->id}")->assertNotFound();
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_guest_cart_merges_into_the_account_on_login(): void
    {
        $a = $this->variantOf('Candle A');
        $b = $this->variantOf('Candle B');
        $user = User::factory()->create(['password' => 'secret-pass']);

        // The customer already had Candle A x1 saved on their account.
        $own = Cart::create(['token' => 'own-token', 'user_id' => $user->id]);
        $own->items()->create(['product_variant_id' => $a->id, 'quantity' => 1]);

        // As a guest they add A x2 and B x1.
        $this->post('/cart/items', ['variant_id' => $a->id, 'quantity' => 2]);
        $token = $this->cartCookie();
        $this->withCookie(CartService::COOKIE, $token)->post('/cart/items', ['variant_id' => $b->id]);

        $this->withCookie(CartService::COOKIE, $token)
            ->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect();

        $this->assertSame(1, Cart::count());
        $this->assertSame(3, $own->items()->where('product_variant_id', $a->id)->value('quantity'));
        $this->assertSame(1, $own->items()->where('product_variant_id', $b->id)->value('quantity'));

        $this->actingAs($user)->get('/cart')->assertSee('Candle A')->assertSee('Candle B');
        $this->actingAs($user)->get('/')->assertSee('Bag, 4 items');
    }

    public function test_a_new_customer_keeps_their_guest_cart_after_registering(): void
    {
        $variant = $this->variantOf('Oud Candle');
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $token = $this->cartCookie();

        $this->withCookie(CartService::COOKIE, $token)->post('/register', [
            'name' => 'Mariam Ali', 'email' => 'mariam@example.com', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect();

        $user = User::where('email', 'mariam@example.com')->firstOrFail();
        $this->assertSame(2, $user->cart->items()->value('quantity'));
    }
}
