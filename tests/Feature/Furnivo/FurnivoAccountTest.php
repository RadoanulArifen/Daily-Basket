<?php

namespace Tests\Feature\Furnivo;

use App\Models\User;

class FurnivoAccountTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function customer(): User
    {
        return User::factory()->create(['name' => 'Layla Ahmed', 'email' => 'layla@example.com']);
    }

    public function test_the_account_pages_share_the_designs_shell(): void
    {
        $this->actingAs($this->customer(), 'web');

        foreach (['/account', '/account/orders', '/account/addresses'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('Hey, Layla')
                ->assertSeeInOrder(['Orders', 'Your details', 'Addresses', 'Saved pieces', 'Sign out'], false);
        }
    }

    public function test_order_history_lists_orders_with_their_pieces(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'web');

        // Place a real order the way a customer would.
        $variant = \App\Models\Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->post('/cart/items', ['variant_id' => $variant, 'quantity' => 1])->assertRedirect();
        $this->post('/checkout', [
            'email' => $customer->email, 'name' => $customer->name, 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = \App\Models\Order::query()->latest('id')->firstOrFail();

        $this->get('/account/orders')->assertOk()
            ->assertSee($order->number)
            ->assertSee('Nord Accent Chair')
            ->assertSee('Order')->assertSee('Placed')->assertSee('Total');
    }

    public function test_an_account_with_no_orders_is_pointed_at_the_shop(): void
    {
        $this->actingAs($this->customer(), 'web');

        $this->get('/account/orders')->assertOk()
            ->assertSee(__('store.account.no_orders'))
            ->assertSee('Browse the collection');
    }

    public function test_addresses_can_be_added_from_the_account(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'web');

        $this->get('/account/addresses')->assertOk()->assertSee(__('store.account.no_addresses'));
        $this->get('/account/addresses/new')->assertOk()->assertSee(__('store.checkout.emirate'));

        $this->post('/account/addresses', [
            'label' => 'Home', 'name' => 'Layla Ahmed', 'phone' => '+971501234567',
            'city' => 'Jumeirah', 'address_line' => '12 Test Street', 'emirate' => 'dubai', 'is_default' => 1,
        ])->assertRedirect();

        $this->get('/account/addresses')->assertOk()->assertSee('12 Test Street')->assertSee(__('store.addresses.default'));
    }

    public function test_account_pages_need_a_signed_in_customer(): void
    {
        foreach (['/account', '/account/orders', '/account/addresses'] as $url) {
            $this->get($url)->assertRedirect(store_route('login'));
        }
    }

    public function test_sign_in_and_registration_use_the_theme(): void
    {
        $this->get('/login')->assertOk()->assertSee(__('store.auth.login_title'))->assertSee(__('store.auth.forgot'));
        $this->get('/register')->assertOk()->assertSee(__('store.auth.register_title'));
        $this->get('/forgot-password')->assertOk()->assertSee(__('store.auth.send_link'));
    }

    public function test_a_customer_can_sign_in_and_out(): void
    {
        $customer = $this->customer();

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticated('web');

        $this->post('/logout')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_a_wrong_password_says_so(): void
    {
        $customer = $this->customer();

        $this->from('/login')->post('/login', ['email' => $customer->email, 'password' => 'not-the-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }
}
