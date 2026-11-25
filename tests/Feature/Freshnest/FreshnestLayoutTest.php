<?php

namespace Tests\Feature\Freshnest;

use App\Models\Product;
use App\Models\User;

/** Header, footer and cart drawer from the design, with the store's own data. */
class FreshnestLayoutTest extends FreshnestTestCase
{
    public function test_header_shows_the_design_with_store_data(): void
    {
        $this->seedFreshnest();

        $this->get('/shop')->assertOk()
            ->assertSee('Free delivery on orders over $50', false)
            ->assertSee('Daily Basket')
            ->assertSee('Search for product...', false)
            ->assertSee('All Categories')
            ->assertSee('Meat &amp; Fish', false)
            ->assertSee('Only This Weekend')
            ->assertSee('Super Discount')
            ->assertSee('BUY 2 GET 1')
            ->assertSee('Deals of the Day')
            ->assertDontSee('Compare')
            ->assertDontSee('Select a Location');
    }

    public function test_footer_uses_menus_and_store_details(): void
    {
        $this->seedFreshnest();

        $this->get('/shop')->assertOk()
            ->assertSee('Fresh from the farm in 45 minutes')
            ->assertSee('Free delivery on every order over $50.', false)
            ->assertSee('Our Growers')
            ->assertSee('Returns &amp; Refunds', false)
            ->assertSee('+1 (800) 555-0132')
            ->assertSee('hello@dailybasket.example')
            ->assertSee('© '.now()->year.' Daily Basket. All rights reserved.', false)
            ->assertDontSee('Grocers')
            ->assertDontSee('Gift Cards')
            ->assertDontSee('Store Locator');
    }

    public function test_top_bar_follows_the_shipping_settings(): void
    {
        $this->seedFreshnest();
        settings()->setMany('shipping', ['free_threshold' => '75.00']);

        $this->get('/shop')->assertSee('Free delivery on orders over $75', false);
    }

    public function test_signed_in_customer_sees_the_account_menu(): void
    {
        $this->seedFreshnest();
        $user = User::factory()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);

        $this->actingAs($user, 'web')->get('/shop')->assertOk()
            ->assertSee('Hi, Jamie')
            ->assertSee('JR')
            ->assertSee('Saved Addresses')
            ->assertSee('Sign out');
    }

    public function test_cart_drawer_shows_delivery_progress_and_total(): void
    {
        $this->seedFreshnest();
        $variant = Product::query()->where('slug', 'baby-leaf-spinach')->firstOrFail()->variants()->orderBy('position')->firstOrFail();
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();
        // PHPUnit keeps no cookies between requests: send the guest cart's cookie back.
        $token = \App\Models\Cart::query()->value('token');

        $this->withCredentials()->withCookie(\App\Services\Cart\CartService::COOKIE, $token)->get('/cart/drawer')->assertOk()
            ->assertSee('Your cart')
            ->assertSee('Add $45.80 more for free delivery', false)
            ->assertSee('Baby Leaf Spinach')
            ->assertSee('250 g bag · $2.10', false)
            ->assertSee('$4.20')
            ->assertSee('$4.99')
            ->assertSee('Checkout · $9.19', false);
    }

    public function test_empty_cart_drawer(): void
    {
        $this->seedFreshnest();

        $this->get('/cart/drawer')->assertOk()->assertSee('Your cart is empty')->assertSee('Start shopping');
    }

    public function test_no_design_hover_attribute_is_left_in_the_theme(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('themes/freshnest/views'), \FilesystemIterator::SKIP_DOTS)) as $file) {
            $this->assertStringNotContainsString('style-hover=', file_get_contents($file->getPathname()), $file->getPathname());
        }
    }
}
