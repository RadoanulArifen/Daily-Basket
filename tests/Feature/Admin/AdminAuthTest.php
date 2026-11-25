<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Notifications\Admin\ResetPasswordNotification;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_and_not_indexed(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Sign in to your store');
    }

    public function test_guests_are_sent_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }

    public function test_admin_can_sign_in_and_out(): void
    {
        $admin = Admin::factory()->create(['email' => 'owner@example.com']);

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_wrong_password_is_rejected(): void
    {
        Admin::factory()->create(['email' => 'owner@example.com']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'wrong-password'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        Admin::factory()->create(['email' => 'owner@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'nope']);
        }

        $this->post('/admin/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_a_logged_in_customer_cannot_open_the_admin(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_customer_credentials_do_not_work_on_the_admin_login(): void
    {
        User::factory()->create(['email' => 'shopper@example.com']);

        $this->post('/admin/login', ['email' => 'shopper@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_reset_link_points_to_the_admin_reset_page(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();

        $this->post('/admin/forgot-password', ['email' => $admin->email])->assertSessionHas('status');

        Notification::assertSentTo($admin, ResetPasswordNotification::class, function ($notification) use ($admin) {
            $mail = $notification->toMail($admin);

            return str_contains($mail->actionUrl, '/admin/reset-password/');
        });
    }

    public function test_admin_can_switch_panel_language(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->post('/admin/locale', ['locale' => 'ar'])->assertRedirect();

        $this->assertSame('ar', $admin->fresh()->locale);
        $this->seedCurrencies();
        $this->actingAs($admin->fresh(), 'admin')->get('/admin')->assertSee('dir="rtl"', false);
    }
}
