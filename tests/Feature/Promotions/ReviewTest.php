<?php

namespace Tests\Feature\Promotions;

use App\Models\Admin;
use App\Models\ProductReview;
use App\Models\User;
use Tests\Feature\Storefront\StorefrontTestCase;

class ReviewTest extends StorefrontTestCase
{
    public function test_customers_review_admin_approves_and_rating_updates(): void
    {
        $product = $this->makeProduct('Woven Cushion');
        $user = User::factory()->create(['name' => 'Fatima Al Hashimi']);

        $this->get('/products/woven-cushion')->assertSee('Sign in to write a review');
        $this->post('/products/woven-cushion/reviews', ['rating' => 5, 'body' => 'Lovely colours'])->assertRedirect('/login');

        $this->actingAs($user)->post('/products/woven-cushion/reviews', ['rating' => 4, 'title' => 'Beautiful', 'body' => 'Lovely colours and well made.'])
            ->assertSessionHas('store_toast');
        $review = ProductReview::sole();
        $this->assertSame('pending', $review->status);
        $this->assertSame('Fatima A', $review->name);  // first name + initial only
        $this->assertFalse($review->verified_purchase);

        // Not shown until approved; a second review is refused.
        $this->get('/products/woven-cushion')->assertDontSee('Lovely colours');
        $this->actingAs($user)->post('/products/woven-cushion/reviews', ['rating' => 1, 'body' => 'Changed my mind!!'])->assertSessionHasErrors('body');

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/reviews')->assertOk()->assertSee('Lovely colours');
        $this->put("/admin/reviews/{$review->id}", ['status' => 'approved'])->assertSessionHas('success');

        $product->refresh();
        $this->assertSame(1, $product->rating_count);
        $this->assertSame('4.00', $product->rating_average);
        $this->get('/products/woven-cushion')->assertSee('Lovely colours')->assertSee('4.0 out of 5');
        $this->get('/shop')->assertSee('(1)');

        $this->delete("/admin/reviews/{$review->id}");
        $this->assertSame(0, $product->fresh()->rating_count);
    }

    public function test_validation_and_hidden_products(): void
    {
        $user = User::factory()->create();
        $this->makeProduct('Draft Thing', ['status' => 'draft']);
        $this->makeProduct('Live Thing');

        $this->actingAs($user)->post('/products/live-thing/reviews', ['rating' => 6, 'body' => 'short'])->assertSessionHasErrors(['rating', 'body']);
        $this->actingAs($user)->post('/products/draft-thing/reviews', ['rating' => 5, 'body' => 'Great product indeed'])->assertNotFound();
    }
}
