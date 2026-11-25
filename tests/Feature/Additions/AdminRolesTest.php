<?php

namespace Tests\Feature\Additions;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Notifications\Admin\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Storefront\StorefrontTestCase;

class AdminRolesTest extends StorefrontTestCase
{
    private function as(AdminRole $role): Admin
    {
        $admin = Admin::factory()->create(['role' => $role->value]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_each_role_reaches_only_its_areas(): void
    {
        $pages = [
            '/admin/orders' => 'orders', '/admin/customers' => 'orders', '/admin/inventory' => 'inventory',
            '/admin/products' => 'catalog', '/admin/media' => 'catalog', '/admin/pages' => 'content',
            '/admin/reviews' => 'reviews', '/admin/discounts' => 'marketing', '/admin/analytics' => 'analytics',
            '/admin/activity' => 'activity', '/admin/settings' => 'settings', '/admin/admins' => 'admins',
        ];

        foreach (AdminRole::cases() as $role) {
            $this->as($role);
            foreach ($pages as $url => $area) {
                $this->get($url)->assertStatus($role->allows($area) ? 200 : 403);
            }
            // Everyone keeps the dashboard and their own account.
            $this->get('/admin')->assertOk();
            $this->get('/admin/account')->assertOk();
        }
    }

    public function test_navigation_only_shows_allowed_sections(): void
    {
        $this->as(AdminRole::Orders);
        $this->get('/admin')->assertSee('/admin/orders', false)->assertDontSee('/admin/products"', false)
            ->assertDontSee('/admin/settings"', false)->assertDontSee('Sales, last 30 days' === '' ? 'x' : 'Setup');

        $this->as(AdminRole::Content);
        $this->get('/admin')->assertSee('/admin/products', false)->assertDontSee('/admin/orders"', false)->assertDontSee('Sales, last 30 days');
    }

    public function test_owner_invites_an_admin_who_sets_their_own_password(): void
    {
        Notification::fake();
        $this->as(AdminRole::Owner);

        $this->post('/admin/admins', ['name' => 'Aisha', 'email' => 'Aisha@Example.com', 'role' => 'orders'])->assertRedirect('/admin/admins');

        $aisha = Admin::where('email', 'aisha@example.com')->firstOrFail();
        $this->assertSame(AdminRole::Orders, $aisha->role);
        Notification::assertSentTo($aisha, ResetPasswordNotification::class);

        $this->get('/admin/admins')->assertSee('Aisha')->assertSee('Hasn’t signed in yet');
    }

    public function test_switched_off_admins_are_signed_out_and_cannot_sign_in(): void
    {
        $owner = $this->as(AdminRole::Owner);
        $staff = Admin::factory()->create(['role' => 'orders', 'email' => 'staff@example.com', 'password' => 'secret-pass-1']);

        $this->put("/admin/admins/{$staff->id}", ['name' => $staff->name, 'email' => $staff->email, 'role' => 'orders', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($staff->fresh(), 'admin')->get('/admin/orders')->assertRedirect('/admin/login');
        auth('admin')->logout();
        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'secret-pass-1'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_the_store_always_keeps_an_owner_and_you_cannot_lock_yourself_out(): void
    {
        $owner = $this->as(AdminRole::Owner);

        $this->put("/admin/admins/{$owner->id}", ['name' => $owner->name, 'email' => $owner->email, 'role' => 'manager', 'is_active' => '1'])->assertSessionHasErrors('role');
        $this->delete("/admin/admins/{$owner->id}")->assertSessionHas('error');

        $second = Admin::factory()->create(['role' => 'owner']);
        $this->actingAs($second, 'admin');
        $this->delete("/admin/admins/{$owner->id}")->assertSessionHas('success');
        $this->assertSame(1, Admin::where('role', 'owner')->count());
    }
}
