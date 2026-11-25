<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\Settings\SettingsService;
use Tests\Feature\Storefront\StorefrontTestCase;

abstract class CheckoutTestCase extends StorefrontTestCase
{
    protected ?string $cartToken = null;

    protected function setUp(): void
    {
        parent::setUp();

        settings()->setMany('general', ['store_email' => 'owner@example.com']);
        app(SettingsService::class)->flush();
    }

    /** Put a variant in a guest bag and remember the bag cookie. */
    protected function addToBag(ProductVariant $variant, int $quantity = 1): void
    {
        $this->cookieRequest()->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => $quantity])->assertSessionHasNoErrors();
        $this->cartToken ??= Cart::query()->whereNull('user_id')->latest('id')->value('token');
    }

    protected function cookieRequest(): static
    {
        return $this->cartToken ? $this->withCookie(CartService::COOKIE, $this->cartToken) : $this;
    }

    protected function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'Mariam@Example.com',
            'phone' => '+971 50 123 4567',
            'name' => 'Mariam Al Nuaimi',
            'emirate' => 'dubai',
            'city' => 'Al Barsha',
            'address_line' => 'Street 12, Villa 4',
            'building' => '',
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'notes' => 'Please call first',
        ], $overrides);
    }
}
