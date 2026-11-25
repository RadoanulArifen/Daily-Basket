<?php

namespace Tests\Feature\AdminUi;

use App\Models\Admin;
use App\Models\CodeVersion;
use Tests\Feature\Storefront\StorefrontTestCase;

class OnlineStoreTest extends StorefrontTestCase
{
    private function themeData(array $overrides = []): array
    {
        $sections = collect(\App\Services\Theme\ThemeSettings::SECTIONS)->map(fn ($k) => ['key' => $k, 'visible' => '1'])->all();

        return array_merge(['color_primary' => '#112233', 'color_accent' => '#0a7f5a', 'color_background' => '#ffffff', 'color_surface' => '#eef2f0', 'font' => 'serif', 'sections' => $sections], $overrides);
    }

    public function test_customize_saves_a_draft_that_only_previewing_admins_see_until_published(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        $this->get('/admin/online-store')->assertOk()->assertSee('Customize');
        $this->get('/admin/online-store/customize')->assertOk()->assertSee('Homepage sections');

        // Hide the story and move featured to the top.
        $sections = collect(\App\Services\Theme\ThemeSettings::SECTIONS)->reject(fn ($k) => $k === 'featured')->prepend('featured')
            ->map(fn ($k) => ['key' => $k, 'visible' => $k === 'story' ? '0' : '1'])->values()->all();
        $this->put('/admin/online-store/customize', $this->themeData(['sections' => $sections]))->assertSessionHas('success');

        // Not previewing: the store still looks the same.
        $this->get('/')->assertDontSee('--color-brand-red:#0a7f5a', false)->assertSee('A Bedouin craft');

        // Previewing (the iframe / "Preview store"): the draft shows, with a banner.
        $this->get('/admin/online-store/preview')->assertRedirect();
        $home = $this->get('/')->assertSee('--color-brand-red:#0a7f5a', false)->assertSee('Georgia', false)->assertSee('previewing unpublished theme changes')
            ->assertDontSee('A Bedouin craft');
        $html = $home->getContent();
        $this->assertLessThan(strpos($html, 'id="featured-heading"') ?: PHP_INT_MAX, strpos($html, 'Handmade in the Emirates') ?: 0);

        // A customer (no admin session) never sees the draft.
        auth('admin')->logout();
        $this->get('/')->assertDontSee('--color-brand-red:#0a7f5a', false);

        // Publish: everyone sees it.
        $this->actingAs($admin, 'admin')->post('/admin/online-store/customize/publish')->assertSessionHas('success');
        auth('admin')->logout();
        $this->flushSession();
        $this->get('/')->assertSee('--color-brand-red:#0a7f5a', false)->assertDontSee('A Bedouin craft')->assertDontSee('previewing unpublished');
    }

    public function test_bad_colours_are_refused(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->put('/admin/online-store/customize', $this->themeData(['color_accent' => 'red;} body{display:none']))->assertSessionHasErrors('color_accent');
    }

    public function test_custom_code_gets_nonces_trusted_domains_and_versions(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        $this->put('/admin/online-store/code', [
            'css' => '.x{color:red}</style><script>alert(1)</script>',
            'head' => '<script src="https://connect.facebook.net/en_US/fbevents.js"></script><script>fbq("init","1")</script>',
            'body' => '<div id="chat"></div>',
            'domains' => "https://connect.facebook.net/en_US/fbevents.js\nwww.facebook.com\nnot a domain",
        ])->assertSessionHas('success');
        auth('admin')->logout();

        $home = $this->get('/');
        $html = $home->getContent();
        $this->assertStringContainsString('.x{color:red}<\/style>', $html);                    // can't break out of <style>
        $this->assertMatchesRegularExpression('/<script nonce="[A-Za-z0-9]+" src="https:\/\/connect\.facebook\.net/', $html);
        $this->assertMatchesRegularExpression('/<script nonce="[A-Za-z0-9]+">fbq/', $html);
        $this->assertStringContainsString('<div id="chat"></div>', $html);
        $csp = $home->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://connect.facebook.net', $csp);
        $this->assertStringContainsString('https://www.facebook.com', $csp);
        $this->assertStringNotContainsString('not a domain', $csp);

        // Admin pages never run custom code.
        $this->actingAs($admin, 'admin')->get('/admin')->assertDontSee('fbevents.js', false);

        // Versions: restore the first save.
        $this->put('/admin/online-store/code', ['css' => '', 'head' => '', 'body' => '', 'domains' => '']);
        $first = CodeVersion::oldest('id')->first();
        $this->post("/admin/online-store/code/versions/{$first->id}")->assertSessionHas('success');
        $this->assertStringContainsString('fbevents.js', settings()->get('code.head'));
        $this->assertSame(3, CodeVersion::count());
    }

    public function test_password_protection(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->put('/admin/online-store/preferences', ['password_enabled' => '1', 'password_message' => ['en' => 'Opening on Eid!']])->assertSessionHasErrors('password');
        $this->put('/admin/online-store/preferences', ['password_enabled' => '1', 'password' => 'eid-2026', 'password_message' => ['en' => 'Opening on Eid!']])->assertSessionHas('success');

        $this->get('/shop')->assertOk();   // admins see the store
        auth('admin')->logout();

        $this->get('/shop')->assertRedirect('/password');
        $this->get('/password')->assertOk()->assertSee('Opening on Eid!');
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->post('/webhooks/stripe', [])->assertStatus(400);   // webhooks still reach the site
        $this->post('/password', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/password', ['password' => 'eid-2026'])->assertRedirect(url('/shop'));
        $this->get('/shop')->assertOk();
    }

    public function test_roles(): void
    {
        $this->actingAs(Admin::factory()->create(['role' => 'content']), 'admin');
        $this->get('/admin/online-store/customize')->assertOk();
        $this->get('/admin/online-store/code')->assertForbidden();
        $this->get('/admin/online-store/preferences')->assertForbidden();
        $this->get('/admin')->assertSee('/admin/online-store', false);

        $this->actingAs(Admin::factory()->create(['role' => 'orders']), 'admin');
        $this->get('/admin/online-store/customize')->assertForbidden();
    }
}
