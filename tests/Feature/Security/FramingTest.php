<?php

namespace Tests\Feature\Security;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** ALLOW_FRAMING_FROM: who may show the landing page and storefront in an iframe. */
class FramingTest extends TestCase
{
    use RefreshDatabase;

    /** /landing/ keeps its trailing slash only through the kernel directly (as in LandingPageTest). */
    private function landing(): TestResponse
    {
        return TestResponse::fromBaseResponse($this->app->make(Kernel::class)->handle(Request::create('/landing/')));
    }

    public function test_by_default_only_this_site_may_frame_pages(): void
    {
        $landing = $this->landing()->assertOk();
        $this->assertStringContainsString("frame-ancestors 'self';", $landing->headers->get('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $landing->headers->get('X-Frame-Options'));
    }

    public function test_listed_sites_may_frame_the_landing_page_and_storefront_but_never_the_admin(): void
    {
        config(['store.allow_framing_from' => 'https://vibe96.com, https://www.vibe96.com/ javascript:alert(1)']);

        $landing = $this->landing();
        $this->assertStringContainsString("frame-ancestors 'self' https://vibe96.com https://www.vibe96.com;", $landing->headers->get('Content-Security-Policy'));
        $this->assertNull($landing->headers->get('X-Frame-Options'));
        $this->assertStringNotContainsString('javascript', $landing->headers->get('Content-Security-Policy'));

        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class]);
        $home = $this->get('/');
        $this->assertStringEndsWith("frame-ancestors 'self' https://vibe96.com https://www.vibe96.com", $home->headers->get('Content-Security-Policy'));
        $this->assertNull($home->headers->get('X-Frame-Options'));

        $admin = $this->get('/admin/login');
        $this->assertSame('DENY', $admin->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'none'", $admin->headers->get('Content-Security-Policy'));
    }
}
