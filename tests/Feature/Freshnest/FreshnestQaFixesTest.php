<?php

namespace Tests\Feature\Freshnest;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartService;

/** QA report 15 Oct 2026: checkout summary, step validation and named deals in FreshNest. */
class FreshnestQaFixesTest extends FreshnestTestCase
{
    private function withCartOf(string $slug, int $qty)
    {
        $variant = Product::query()->where('slug', $slug)->firstOrFail()->variants()->orderBy('position')->firstOrFail();
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => $qty])->assertOk();

        return $this->withCredentials()->withCookie(CartService::COOKIE, Cart::query()->value('token'));
    }

    public function test_checkout_summary_is_printed_by_the_server(): void
    {
        $this->seedFreshnest();

        $html = $this->withCartOf('baby-leaf-spinach', 2)->get('/checkout')->assertOk()->getContent();

        // Delivery and Total have their values before any script runs.
        $this->assertMatchesRegularExpression('#x-text="fmt\(t\.shipping\)">\$4\.99</span>#', $html);
        $this->assertMatchesRegularExpression('#<div class="ck-total"><span>Total</span><span x-text="fmt\(t\.total\)">\$\d+\.\d\d</span>#', $html);
        $this->assertDoesNotMatchRegularExpression('#x-text="fmt\(t\.total\)"></span>#', $html);
    }

    public function test_checkout_steps_are_validated_with_messages(): void
    {
        $this->seedFreshnest();

        $html = $this->withCartOf('baby-leaf-spinach', 1)->get('/checkout')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="checkoutForm(', $html);
        $this->assertStringContainsString('@submit="submit($event)"', $html);
        foreach ([1, 2, 3] as $step) {
            $this->assertStringContainsString('data-step="'.$step.'"', $html);
        }
        $this->assertStringContainsString('Please fill in this field.', $html);
        $this->assertStringContainsString('Enter a valid email address', $html);
    }

    public function test_cart_names_the_deal_instead_of_an_unexplained_saving(): void
    {
        $this->seedFreshnest();
        $client = $this->withCartOf('organic-hass-avocado', 2);

        foreach (['/cart', '/checkout'] as $url) {
            $client->get($url)->assertOk()
                ->assertSee('Deal: Buy 1 Get 1')
                ->assertDontSee('>Savings<', false);
        }
    }
}
