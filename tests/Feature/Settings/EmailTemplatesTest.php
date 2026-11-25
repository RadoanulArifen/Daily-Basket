<?php

namespace Tests\Feature\Settings;

use App\Mail\CustomerMessage;
use App\Models\Admin;
use App\Models\User;
use App\Services\Notifications\EmailTemplates;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_template_can_be_previewed(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        foreach (EmailTemplates::ALL_EVENTS as $event) {
            $response = $this->get("/admin/settings/emails/{$event}/preview");
            $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->assertStringContainsString('<html', $response->getContent(), "{$event} renders an email");
        }
    }

    public function test_an_unknown_template_is_not_found(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/settings/emails/made-up/preview')->assertNotFound();
    }

    public function test_previews_are_for_admins_only(): void
    {
        $this->get('/admin/settings/emails/welcome/preview')->assertRedirect();
    }

    public function test_wording_edited_in_the_admin_is_what_customers_get(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        app(SettingsService::class)->setMany('emails', [
            'welcome_subject' => ['en' => 'Welcome aboard at {store}'],
            'welcome_intro' => ['en' => 'Lovely to have you with us.'],
        ]);

        $preview = $this->get('/admin/settings/emails/welcome/preview')->assertOk();
        $preview->assertSee('Lovely to have you with us.');

        $mail = new CustomerMessage(event: 'welcome', vars: ['{name}' => 'Layla']);
        $this->assertStringContainsString('Welcome aboard at', $mail->envelope()->subject);
    }

    public function test_a_welcome_email_is_sent_when_an_account_is_created(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Layla Ahmed', 'email' => 'layla@example.com', 'phone' => '+971501234567',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'welcome' && $mail->hasTo('layla@example.com'));
    }

    public function test_an_enquiry_is_acknowledged_to_the_customer(): void
    {
        Mail::fake();

        $this->post('/custom-order', [
            'name' => 'Layla Ahmed', 'email' => 'layla@example.com',
            'dimensions' => '240 x 95 cm', 'material' => 'walnut',
        ])->assertRedirect();

        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'enquiry' && $mail->hasTo('layla@example.com'));
    }

    public function test_the_admin_can_send_itself_a_copy(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->post('/admin/settings/emails/welcome/send')->assertRedirect();

        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->hasTo($admin->email));
    }
}
