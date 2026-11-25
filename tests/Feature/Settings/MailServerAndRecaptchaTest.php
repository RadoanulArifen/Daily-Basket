<?php

namespace Tests\Feature\Settings;

use App\Models\Admin;
use App\Services\Security\Recaptcha;
use App\Services\Settings\Credentials;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailServerAndRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_server_details_are_saved_and_used(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->put('/admin/settings/credentials/smtp', [
            'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
            'username' => 'info@example.com', 'password' => 'app-password',
            'from_address' => 'info@example.com', 'from_name' => 'Furnivo',
        ])->assertRedirect();

        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame('587', (string) config('mail.mailers.smtp.port'));
        $this->assertSame('info@example.com', config('mail.from.address'));
        $this->assertSame('Furnivo', config('mail.from.name'));
        $this->assertSame('smtp', config('mail.default'), 'Saved details switch sending to SMTP.');
    }

    public function test_the_password_is_stored_encrypted_and_shown_masked(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->put('/admin/settings/credentials/smtp', ['host' => 'smtp.gmail.com', 'password' => 'super-secret-password']);

        $stored = app(SettingsService::class)->group('credentials')['smtp_password'];
        $this->assertNotSame('super-secret-password', $stored);
        $this->assertStringContainsString('••••', app(Credentials::class)->display('smtp', 'password'));
        $this->assertSame('smtp.gmail.com', app(Credentials::class)->display('smtp', 'host'));
    }

    public function test_recaptcha_is_off_until_both_keys_are_set(): void
    {
        config(['services.recaptcha.site_key' => null, 'services.recaptcha.secret' => null]);
        $this->assertFalse(app(Recaptcha::class)->enabled());
        $this->assertTrue(app(Recaptcha::class)->verify(null), 'With no keys, forms are not blocked.');

        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'secret']);
        $this->assertTrue(app(Recaptcha::class)->enabled());
        $this->assertFalse(app(Recaptcha::class)->verify(null), 'With keys, a missing token fails.');
    }

    public function test_a_low_score_is_treated_as_a_bot(): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'secret', 'services.recaptcha.min_score' => 0.5]);
        Http::fake(['*' => Http::response(['success' => true, 'score' => 0.1])]);

        $this->assertFalse(app(Recaptcha::class)->verify('token'));
    }

    public function test_a_good_score_passes(): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'secret', 'services.recaptcha.min_score' => 0.5]);
        Http::fake(['*' => Http::response(['success' => true, 'score' => 0.9])]);

        $this->assertTrue(app(Recaptcha::class)->verify('token'));
    }

    public function test_google_being_unreachable_does_not_block_customers(): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'secret']);
        Http::fake(fn () => throw new \RuntimeException('network down'));

        $this->assertTrue(app(Recaptcha::class)->verify('token'));
    }

    public function test_public_forms_are_checked_when_recaptcha_is_on(): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'secret']);
        Http::fake(['*' => Http::response(['success' => false])]);

        $this->postJson('/custom-order', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'A message long enough to pass.',
            'g-recaptcha-response' => 'bad-token',
        ])->assertStatus(422)->assertJsonValidationErrors('g-recaptcha-response');
    }
}
