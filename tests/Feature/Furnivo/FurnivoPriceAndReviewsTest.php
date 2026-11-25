<?php

namespace Tests\Feature\Furnivo;

use App\Models\Product;
use App\Support\Money;

class FurnivoPriceAndReviewsTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_a_was_price_is_shown_struck_through_with_the_saving(): void
    {
        $product = Product::query()->where('slug', 'lucca-sofa')->firstOrFail();
        foreach ($product->variants as $variant) {
            $variant->update(['compare_at_price' => Money::ofMinor((int) round($variant->price->minor * 1.25), $variant->price->currency)]);
        }

        $page = $this->get("/products/{$product->slug}")->assertOk();
        $page->assertSee('line-through', false)->assertSee('20% off', false);

        // And on the cards in a listing.
        $this->get('/collections/seating')->assertOk()->assertSee('line-through', false);
    }

    public function test_a_product_without_a_was_price_shows_one_price_only(): void
    {
        $product = Product::query()->where('slug', 'nord-accent')->firstOrFail();
        $product->variants()->update(['compare_at_price' => null]);

        $this->get("/products/{$product->slug}")->assertOk()->assertDontSee('% off', false);
    }

    public function test_the_review_count_is_hidden_when_there_are_none(): void
    {
        $product = Product::query()->where('slug', 'halo-floor')->firstOrFail();
        $product->reviews()->delete();
        $product->forceFill(['rating_count' => 0, 'rating_average' => 0])->save();

        $this->get("/products/{$product->slug}")->assertOk()
            ->assertSee(__('store.product.no_reviews_yet'))
            ->assertDontSee('0 reviews');
    }

    public function test_sample_reviews_can_be_added_to_products_that_have_none(): void
    {
        $product = Product::query()->where('slug', 'halo-floor')->firstOrFail();
        $product->reviews()->delete();
        $product->forceFill(['rating_count' => 0])->save();

        $before = \App\Models\ProductReview::query()->count();
        $this->artisan('store:demo-reviews')->assertSuccessful();

        $this->assertGreaterThan(0, $product->fresh()->reviews()->count());
        $this->assertGreaterThan($before, \App\Models\ProductReview::query()->count());

        // Running it again leaves the reviews as they are.
        $now = \App\Models\ProductReview::query()->count();
        $this->artisan('store:demo-reviews')->assertSuccessful();
        $this->assertSame($now, \App\Models\ProductReview::query()->count());
    }

    public function test_the_star_row_and_count_match_the_reviews_on_the_page(): void
    {
        $product = Product::query()->where('slug', 'arc-floor')->firstOrFail();

        // A stored count that does not match reality, as demo data can leave behind.
        $product->forceFill(['rating_count' => 41, 'rating_average' => 4.8])->save();
        $real = $product->reviews()->where('status', 'approved')->count();
        $this->assertNotSame(41, $real);

        $page = $this->get("/products/{$product->slug}")->assertOk();
        $page->assertSee(trans_choice('store.product.review_count', $real, ['count' => $real]), false);
        $page->assertDontSee('41 reviews');
    }

    public function test_the_stored_count_can_be_put_back_in_step(): void
    {
        $product = Product::query()->where('slug', 'arc-floor')->firstOrFail();
        $product->forceFill(['rating_count' => 41, 'rating_average' => 4.8])->save();

        $this->artisan('store:sync-ratings')->assertSuccessful();

        $product->refresh();
        $this->assertSame($product->reviews()->where('status', 'approved')->count(), $product->rating_count);
    }

    public function test_the_stars_shown_follow_the_score(): void
    {
        $product = Product::query()->where('slug', 'arc-floor')->firstOrFail();
        $product->reviews()->update(['rating' => 3]);
        \App\Models\ProductReview::refreshProductRating($product->id);

        // Three dark stars, two greyed out.
        $html = $this->get("/products/{$product->slug}")->assertOk()->getContent();
        $summary = substr($html, strpos($html, 'href="#reviews"'), 700);
        $this->assertSame(2, substr_count($summary, 'color: rgb(201, 197, 191);'));
    }
}
