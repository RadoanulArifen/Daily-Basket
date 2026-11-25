<?php

namespace Tests\Feature\Storefront;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCurrencies();
    }

    public function test_customer_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Aisha Rahman',
            'email' => 'Aisha@Example.com',
            'phone' => '+971 50 123 4567',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/account');

        $user = User::where('email', 'aisha@example.com')->firstOrFail();
        $this->assertSame('+971501234567', $user->phone);
        $this->assertSame('en', $user->locale);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_arabic_registration_saves_arabic_preference(): void
    {
        $this->post('/ar/register', [
            'name' => 'عائشة',
            'email' => 'aisha@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/ar/account');

        $this->assertSame('ar', User::firstOrFail()->locale);
    }

    public function test_customer_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'shopper@example.com']);

        $this->post('/login', ['email' => 'shopper@example.com', 'password' => 'password'])
            ->assertRedirect('/account');
        $this->assertAuthenticatedAs($user, 'web');

        $this->get('/account')->assertOk()->assertSee($user->email);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('web');
    }

    public function test_deactivated_customers_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'blocked@example.com']);

        $this->post('/login', ['email' => 'blocked@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_account_page_requires_login_in_the_right_language(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/ar/account')->assertRedirect('/ar/login');
    }

    public function test_admins_cannot_use_the_customer_login(): void
    {
        \App\Models\Admin::factory()->create(['email' => 'owner@example.com']);

        $this->post('/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_link_uses_the_customers_language(): void
    {
        Notification::fake();
        $user = User::factory()->create(['locale' => 'ar']);

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            return str_contains($notification->toMail($user)->actionUrl, '/ar/reset-password/');
        });
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }
}
