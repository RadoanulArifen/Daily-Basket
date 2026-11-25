<?php

namespace Tests\Feature\Freshnest;

use App\Models\User;

/** FreshNest address forms ask for country + state, not the old emirate list. */
class FreshnestCountryFieldsTest extends FreshnestTestCase
{
    public function test_the_address_form_has_country_and_state(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->get('/account/addresses/new')->assertOk()
            ->assertSee('name="country"', false)->assertSee('name="state"', false)
            ->assertDontSee('name="emirate"', false);
    }
}
