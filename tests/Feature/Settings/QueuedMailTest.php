<?php

namespace Tests\Feature\Settings;

use App\Mail\ContactMessage;
use App\Models\Admin;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QueuedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_waiting_emails_can_be_sent_from_the_admin(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'array']);
        $this->actingAs(Admin::factory()->create(), 'admin');

        Mail::to('someone@example.com')->queue(new ContactMessage([
            'name' => 'Test', 'email' => 't@example.com', 'message' => 'Hello there, testing.', 'phone' => null,
        ]));
        $this->assertSame(1, DB::table('jobs')->count());

        $this->post('/admin/settings/mail-server/run-queue')->assertRedirect();

        $this->assertSame(0, DB::table('jobs')->count(), 'The waiting email has gone out.');
    }

    public function test_the_admin_warns_while_emails_are_waiting(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'array']);
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/settings/mail-server')->assertOk()->assertDontSee(__('admin.settings.mail_server.queue_send_now'));

        Mail::to('someone@example.com')->queue(new ContactMessage([
            'name' => 'Test', 'email' => 't@example.com', 'message' => 'Hello there, testing.', 'phone' => null,
        ]));

        $this->get('/admin/settings/mail-server')->assertOk()->assertSee(__('admin.settings.mail_server.queue_send_now'));
    }

    public function test_the_home_page_description_fits_what_the_admin_allows(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);

        $description = app(SettingsService::class)->trans('general.meta_description');
        $this->assertLessThanOrEqual(160, mb_strlen((string) $description), 'Otherwise Store details cannot be saved.');

        // And the settings form accepts it back unchanged.
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->put('/admin/settings/general', [
            'store_name' => ['en' => 'Furnivo'],
            'store_email' => 'hello@furnivo.com',
            'meta_description' => ['en' => $description],
        ])->assertSessionDoesntHaveErrors();
    }
}
