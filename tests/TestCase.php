<?php

namespace Tests;

use App\Models\Currency;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views are tested without compiled Vite assets.
        $this->withoutVite();
    }

    /** Minimal store data most pages expect: base currency + one extra. */
    protected function seedCurrencies(): void
    {
        Currency::factory()->base()->create();
        Currency::factory()->create(); // USD
    }

    /**
     * Each simulated request starts with fresh per-request services (cart, wishlist),
     * as it would on a real server, instead of reusing the previous request's.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
