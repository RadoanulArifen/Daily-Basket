<?php

namespace Tests\Feature\Freshnest;

/** The FreshNest theme's own error page; the admin keeps the plain one. */
class FreshnestErrorPageTest extends FreshnestTestCase
{
    public function test_a_missing_page_uses_the_freshnest_design(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()
            ->assertSee('Error 404')->assertSee('Back to home')->assertSee('Shop all products');
    }
}
