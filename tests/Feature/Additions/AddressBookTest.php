<?php

namespace Tests\Feature\Additions;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Tests\Feature\Checkout\CheckoutTestCase;

class AddressBookTest extends CheckoutTestCase
{
    private function address(array $overrides = []): array
    {
        return array_merge(['label' => 'Home', 'name' => 'Mariam', 'phone' => '050 123 4567', 'emirate' => 'dubai', 'city' => 'Al Barsha', 'address_line' => 'Street 12, Villa 4', 'building' => ''], $overrides);
    }

    public function test_customers_manage_their_address_book(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/account/addresses')->assertOk()->assertSee('No saved addresses yet');
        $this->post('/account/addresses', $this->address())->assertRedirect('/account/addresses');
        $this->post('/account/addresses', $this->address(['label' => 'Office', 'city' => 'DIFC', 'address_line' => 'Gate Building']))->assertRedirect();

        [$home, $office] = $user->addresses()->orderBy('id')->get()->all();
        $this->assertTrue($home->is_default);          // the first one becomes the default
        $this->assertFalse($office->is_default);
        $this->assertSame('+971501234567' === $home->phone ? 1 : 0, 0); // stored digits only
        $this->assertSame('0501234567', $home->phone);

        $this->post("/account/addresses/{$office->id}/default")->assertRedirect();
        $this->assertTrue($office->fresh()->is_default);
        $this->assertFalse($home->fresh()->is_default);

        $this->put("/account/addresses/{$home->id}", $this->address(['city' => 'Jumeirah']))->assertRedirect();
        $this->assertSame('Jumeirah', $home->fresh()->city);

        $this->delete("/account/addresses/{$office->id}")->assertRedirect();
        $this->assertTrue($home->fresh()->is_default); // default passed on
        $this->get('/account')->assertSee('Manage addresses');
    }

    public function test_customers_cannot_touch_someone_elses_address(): void
    {
        $other = User::factory()->create();
        $theirs = CustomerAddress::create(['user_id' => $other->id] + $this->address());

        $this->actingAs(User::factory()->create());
        $this->get("/account/addresses/{$theirs->id}/edit")->assertNotFound();
        $this->put("/account/addresses/{$theirs->id}", $this->address(['city' => 'Hacked']))->assertNotFound();
        $this->delete("/account/addresses/{$theirs->id}")->assertNotFound();
        $this->assertSame('Al Barsha', $theirs->fresh()->city);
    }

    public function test_there_is_a_limit(): void
    {
        $user = User::factory()->create();
        foreach (range(1, CustomerAddress::MAX_PER_CUSTOMER) as $i) {
            CustomerAddress::create(['user_id' => $user->id, 'is_default' => false] + $this->address(['city' => "City {$i}"]));
        }

        $this->actingAs($user)->post('/account/addresses', $this->address())->assertSessionHasErrors('address_line');
    }

    public function test_checkout_saves_new_addresses_and_reuses_existing_ones(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeProduct('Cups')->variants()->first();
        $order = function (array $data) use ($user, $variant) {
            $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);
            $this->actingAs($user)->post('/checkout', $this->checkoutData(['email' => $user->email] + $data))->assertSessionHasNoErrors();
        };

        $order([]);                                          // new → saved
        $order(['address_line' => '  street 12,   villa 4 ']); // same place, typed differently → no duplicate
        $order(['address_line' => 'Tower 9', 'save_address' => '0']); // unticked → not saved
        $order(['address_line' => 'Marina Heights', 'city' => 'Marina']); // another new one → saved and default

        $this->assertSame(2, $user->addresses()->count());
        $this->assertSame('Marina Heights', $user->addresses()->first()->address_line); // default first
        $this->assertSame(4, Order::count());

        // The checkout offers both saved addresses.
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $variant->id]);
        $this->actingAs($user)->get('/checkout')->assertSee('Your saved addresses')->assertSee('Marina Heights')->assertSee('Street 12, Villa 4');
    }
}
