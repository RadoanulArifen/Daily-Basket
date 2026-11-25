<?php

namespace Tests\Feature\Furnivo;

use App\Models\Product;

class FurnivoWishlistTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_an_empty_wishlist_follows_the_design(): void
    {
        $this->get('/wishlist')->assertOk()
            ->assertSeeInOrder(['Home', 'Wishlist', 'Nothing saved yet.', 'Browse the collection'], false)
            ->assertDontSee('Copy link');
    }

    public function test_saved_pieces_appear_with_share_links(): void
    {
        $product = Product::query()->where('slug', 'nord-accent')->firstOrFail();
        $this->post("/wishlist/{$product->id}")->assertRedirect();

        $page = $this->get('/wishlist')->assertOk();
        $page->assertSee('Nord Accent Chair')->assertSee('Copy link')->assertSee('Share');

        // The share links carry the pieces, so they work for whoever opens them.
        $page->assertSee('items=nord-accent', false);
        $page->assertSee('wa.me', false);
        $page->assertSee('mailto:', false);
    }

    public function test_a_shared_link_shows_the_same_pieces_to_anyone(): void
    {
        // A different visitor, with nothing saved of their own.
        $this->get('/wishlist?items=nord-accent,lucca-sofa')->assertOk()
            ->assertSee('Nord Accent Chair')
            ->assertSee('Lucca 3-Seater Sofa')
            ->assertSee('A wishlist someone shared with you.');

        $this->get('/wishlist')->assertOk()->assertSee('Nothing saved yet.');
    }

    public function test_a_shared_link_cannot_show_hidden_products(): void
    {
        Product::query()->where('slug', 'lucca-sofa')->update(['status' => 'draft']);

        $this->get('/wishlist?items=nord-accent,lucca-sofa')->assertOk()
            ->assertSee('Nord Accent Chair')
            ->assertDontSee('Lucca 3-Seater Sofa');
    }

    public function test_the_wishlist_is_kept_out_of_search_engines(): void
    {
        $this->get('/wishlist')->assertOk()->assertSee('noindex', false);
    }
}
