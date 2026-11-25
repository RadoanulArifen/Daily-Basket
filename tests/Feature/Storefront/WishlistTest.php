<?php

namespace Tests\Feature\Storefront;

use App\Models\User;
use App\Models\WishlistItem;

class WishlistTest extends StorefrontTestCase
{
    public function test_guests_can_save_and_unsave_in_their_session(): void
    {
        $product = $this->makeProduct('Palm Cushion');

        $this->postJson("/wishlist/{$product->id}")->assertOk()->assertJson(['saved' => true, 'count' => 1]);
        $this->get('/wishlist')->assertOk()->assertSee('Palm Cushion')->assertSee('Sign in to keep it');

        $this->postJson("/wishlist/{$product->id}")->assertOk()->assertJson(['saved' => false, 'count' => 0]);
        $this->get('/wishlist')->assertSee('Your wishlist is empty');
    }

    public function test_customers_save_to_their_account_and_guest_items_merge_on_login(): void
    {
        $first = $this->makeProduct('First Throw');
        $second = $this->makeProduct('Second Throw');
        $user = User::factory()->create(['password' => 'secret-pass']);
        WishlistItem::create(['user_id' => $user->id, 'product_id' => $first->id]);

        $this->post("/wishlist/{$first->id}");
        $this->post("/wishlist/{$second->id}");
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $user->wishlistItems()->pluck('product_id')->all());

        $this->actingAs($user)->get('/wishlist')->assertSee('First Throw')->assertSee('Second Throw')->assertDontSee('Sign in to keep it');
        $this->actingAs($user)->get('/account')->assertSee('2 saved products');
    }

    public function test_hidden_products_cannot_be_saved_or_shown(): void
    {
        $draft = $this->makeProduct('Draft Vase', ['status' => 'draft']);
        $this->post("/wishlist/{$draft->id}")->assertNotFound();

        $live = $this->makeProduct('Live Vase');
        $this->post("/wishlist/{$live->id}");
        $live->update(['status' => 'archived']);

        // (The "saved" toast from the previous request may still mention it; the grid must not.)
        $this->get('/wishlist')->assertDontSee('/products/live-vase', false)->assertSee('Your wishlist is empty');
    }
}
