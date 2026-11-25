<?php

namespace Tests\Feature\Settings;

use App\Mail\ContactMessage;
use App\Mail\CustomerMessage;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_sent_straight_away_unless_a_worker_is_set_up(): void
    {
        // Queued mail needs `php artisan queue:work`; without one it would never leave.
        // (The test environment pins its own connection, so check what the app ships with.)
        $this->assertStringContainsString(
            "env('QUEUE_CONNECTION', 'sync')",
            file_get_contents(config_path('queue.php')),
            'Out of the box, email is sent immediately rather than queued.'
        );
    }

    public function test_the_mail_server_has_a_timeout(): void
    {
        // A wrong or unreachable server must not hang the page.
        $this->assertNotNull(config('mail.mailers.smtp.timeout'));
        $this->assertLessThanOrEqual(30, (int) config('mail.mailers.smtp.timeout'));
    }

    public function test_a_contact_message_reaches_the_store_and_the_customer(): void
    {
        Mail::fake();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\FurnivoContentSeeder::class]);
        app(\App\Services\Settings\SettingsService::class)->setMany('general', ['store_email' => 'hello@furnivo.com']);

        $page = Page::query()->where('slug', 'contact')->firstOrFail();

        $this->post("/pages/{$page->slug}/contact", [
            'name' => 'Ali Russel', 'email' => 'ali@example.com',
            'message' => 'Asking for a test message about a table.',
        ])->assertRedirect();

        Mail::assertQueued(ContactMessage::class, fn ($mail) => $mail->hasTo('hello@furnivo.com'));
        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'contact' && $mail->hasTo('ali@example.com'));
    }
}
