<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Demo account: sees every screen (owner role by default), cannot change anything, sees no secrets. */
class DemoOwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class]);
        config(['store.demo' => true, 'store.demo_email' => 'demo@example.com', 'store.demo_password' => 'Demo-pass-2026!',
            'services.stripe.secret' => 'sk_test_SECRETVALUE1234']);
    }

    public function test_demo_admin_is_owner_and_can_open_settings_but_not_change_them(): void
    {
        $this->artisan('store:demo-admin')->assertSuccessful();
        $demo = Admin::query()->where('email', 'demo@example.com')->firstOrFail();
        $this->assertSame(AdminRole::Owner, $demo->role);

        Admin::factory()->create(['email' => 'owner.private@example.com']);
        $this->actingAs($demo, 'admin');

        $this->get('/admin/settings/general')->assertOk();
        $this->get('/admin/settings/payments')->assertOk()->assertDontSee('1234')->assertDontSee('SECRETVALUE');
        $this->get('/admin/admins')->assertOk()->assertDontSee('owner.private@example.com');

        $this->from('/admin/settings/general')->put('/admin/settings/general', ['store_name' => ['en' => 'Hacked']])
            ->assertRedirect('/admin/settings/general')->assertSessionHas('error');
    }

    public function test_demo_role_can_be_narrowed(): void
    {
        config(['store.demo_role' => 'manager']);
        $this->artisan('store:demo-admin')->assertSuccessful();
        $this->assertSame(AdminRole::Manager, Admin::query()->where('email', 'demo@example.com')->value('role'));
    }
}
