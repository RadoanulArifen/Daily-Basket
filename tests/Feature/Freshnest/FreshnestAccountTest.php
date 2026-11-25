<?php

namespace Tests\Feature\Freshnest;

use App\Models\User;

/** Sign-in, register and the account area in the design's look. */
class FreshnestAccountTest extends FreshnestTestCase
{
    public function test_sign_in_and_register_pages(): void
    {
        $this->seedFreshnest();

        $this->get('/login')->assertOk()->assertSee('Welcome back')->assertSee('Create account')->assertSee('Forgot password?')->assertSee('name="password"', false);
        $this->get('/register')->assertOk()->assertSee('Create your account')->assertSee('name="password_confirmation"', false);

        $this->post('/register', ['name' => 'Jamie Rivera', 'email' => 'jamie@example.com', 'phone' => '+14155550100', 'password' => 'secret-pass-99', 'password_confirmation' => 'secret-pass-99'])->assertRedirect();
        $this->assertAuthenticated('web');
    }

    public function test_profile_orders_wishlist_and_addresses(): void
    {
        $this->seedFreshnest();
        $user = User::factory()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);
        $me = $this->actingAs($user, 'web');

        $me->get('/account')->assertOk()->assertSee('My Profile')->assertSee('JR')->assertSee('Total orders')->assertSee('Personal information')->assertDontSee('Green Member');
        $me->get('/account/orders')->assertOk()->assertSee('No orders yet');
        $me->get('/wishlist')->assertOk()->assertSee('No saved items yet');
        $me->get('/account/addresses')->assertOk()->assertSee('Add a new address');
        $me->get('/account/addresses/new')->assertOk()->assertSee('Save address');

        $me->post('/account/addresses', ['label' => 'Home', 'name' => 'Jamie Rivera', 'address_line' => '18 Meadow Row', 'city' => 'San Francisco', 'emirate' => 'dubai', 'phone' => '+14155550100', 'is_default' => '1'])->assertRedirect();
        $me->get('/account/addresses')->assertOk()->assertSee('18 Meadow Row')->assertSee('Default');
    }

    public function test_guest_wishlist_page_stands_alone(): void
    {
        $this->seedFreshnest();

        $this->get('/wishlist')->assertOk()->assertSee('Your saved favourites')->assertDontSee('Sign out');
    }
}
