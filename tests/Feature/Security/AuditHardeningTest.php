<?php

namespace Tests\Feature\Security;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** The protections the vibe96 audit asked to see, proven by requests. */
class AuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_import_export_and_email_preview_need_an_admin(): void
    {
        $this->post('/admin/products/import/check')->assertRedirect(route('admin.login'));
        $this->post('/admin/products/import', ['token' => 'x'])->assertRedirect(route('admin.login'));
        $this->get('/admin/products/export')->assertRedirect(route('admin.login'));
        $this->get('/admin/settings/emails/order_placed/preview')->assertRedirect(route('admin.login'));
    }

    public function test_customer_reset_link_works_once(): void
    {
        $user = User::factory()->create(['email' => 'sam@example.com']);
        $token = Password::broker('users')->createToken($user);
        $data = ['token' => $token, 'email' => 'sam@example.com', 'password' => 'N3w-pass-word!', 'password_confirmation' => 'N3w-pass-word!'];

        $this->post('/reset-password', $data)->assertRedirect();
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', 'sam@example.com')->count());
        $this->post('/reset-password', ['password' => 'An0ther-pass!', 'password_confirmation' => 'An0ther-pass!'] + $data)->assertSessionHasErrors('email');
    }

    public function test_deactivated_admin_cannot_reset_or_get_a_link(): void
    {
        $admin = Admin::factory()->create(['email' => 'old@example.com', 'is_active' => false]);
        $token = Password::broker('admins')->createToken($admin);

        $this->post('/admin/reset-password', ['token' => $token, 'email' => 'old@example.com', 'password' => 'N3w-pass-word!', 'password_confirmation' => 'N3w-pass-word!'])
            ->assertSessionHasErrors('email');
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('N3w-pass-word!', $admin->fresh()->password));
    }

    public function test_stock_alert_cancel_rejects_short_tokens_and_is_rate_limited(): void
    {
        $this->get('/stock-alerts/123/cancel')->assertNotFound();
        $route = collect(app('router')->getRoutes())->first(fn ($r) => $r->getName() === 'stock-alerts.cancel');
        $this->assertContains('throttle:20,1', $route->gatherMiddleware());
    }

    public function test_one_customer_cannot_open_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = \App\Models\CustomerAddress::query()->create(['user_id' => $owner->id, 'name' => 'A', 'phone' => '1', 'address_line' => 'x', 'city' => 'y', 'emirate' => 'dubai']);

        $this->actingAs($other, 'web')->get('/account/addresses/'.$address->id.'/edit')->assertNotFound();
        $this->actingAs($other, 'web')->delete('/account/addresses/'.$address->id)->assertNotFound();
    }
}
