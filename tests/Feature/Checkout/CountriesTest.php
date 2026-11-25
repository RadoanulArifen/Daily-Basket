<?php

namespace Tests\Feature\Checkout;

use App\Models\Admin;
use App\Models\Order;
use App\Support\Countries;

/** Country + state on addresses, and Settings → Shipping → Countries. */
class CountriesTest extends CheckoutTestCase
{
    private function bag(): void
    {
        $this->addToBag($this->makeProduct('Lamp', [], [['price' => '100.00', 'stock_quantity' => 5]])->variants()->first(), 1);
    }

    public function test_an_order_stores_country_and_state(): void
    {
        $this->bag();
        $data = $this->checkoutData(['country' => 'US', 'state' => 'California', 'city' => 'San Francisco']);
        unset($data['emirate']);
        $this->cookieRequest()->post('/checkout', $data)->assertRedirect();

        $order = Order::sole();
        $this->assertSame(['US', 'California', null], [$order->ship_country, $order->ship_state, $order->ship_emirate]);
        $this->assertSame('San Francisco, California, United States', $order->shipRegionLine());
    }

    public function test_a_uae_address_keeps_its_emirate_for_aramex(): void
    {
        $this->bag();
        $data = $this->checkoutData(['country' => 'AE', 'state' => 'Sharjah']);
        unset($data['emirate']);
        $this->cookieRequest()->post('/checkout', $data)->assertRedirect();

        $this->assertSame('sharjah', Order::sole()->ship_emirate);
    }

    public function test_only_the_chosen_countries_are_accepted(): void
    {
        settings()->setMany('shipping', ['countries' => ['US', 'CA']]);
        $this->assertSame(['CA', 'US'], collect(Countries::allowedCodes())->sort()->values()->all());

        $this->bag();
        $data = $this->checkoutData(['country' => 'FR', 'state' => 'Paris']);
        unset($data['emirate']);
        $this->cookieRequest()->post('/checkout', $data)->assertSessionHasErrors('country');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_admin_saves_the_countries_setting(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/settings/shipping')->assertOk()->assertSee('Find a country')->assertSee('United Arab Emirates');

        $this->put('/admin/settings/shipping', [
            'countries' => ['AE', 'SA'], 'default_country' => 'AE',
            'flat_enabled' => '1', 'flat_name' => ['en' => 'Standard'], 'flat_rate' => '10.00',
            'free_enabled' => '0', 'free_threshold' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['AE', 'SA'], settings()->get('shipping.countries'));
    }
}
