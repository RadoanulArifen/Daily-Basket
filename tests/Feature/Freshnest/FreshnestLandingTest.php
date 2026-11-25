<?php

namespace Tests\Feature\Freshnest;

/** /landing/ serves the active theme's own landing page when it has one. */
class FreshnestLandingTest extends FreshnestTestCase
{
    public function test_freshnest_landing_page_is_served_with_corrected_figures(): void
    {
        $this->get('/landing/index.html')->assertOk()
            ->assertSee('Daily Basket')->assertSee('>566</strong>', false)->assertDontSee('>414</strong>', false)
            ->assertSee('img/screens/admin.jpg', false);
        $this->get('/landing/img/screens/admin.jpg')->assertOk();
    }

    public function test_other_themes_keep_the_shared_landing_page(): void
    {
        config(['store.theme' => 'furnivo']);
        $this->assertSame(resource_path('landing'), \App\Http\Controllers\LandingController::root());
    }
}
