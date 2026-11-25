<?php

namespace Tests\Feature\Settings;

use App\Mail\CustomerMessage;
use App\Models\Admin;
use App\Services\Settings\Credentials;
use App\Services\Settings\SettingsService;
use App\Support\MailErrorExplainer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\Feature\Storefront\StorefrontTestCase;

/** Settings → Mail server → "Send a test email to…", plain-words errors and the embedded logo. */
class MailServerTestEmailTest extends StorefrontTestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()->forgetInstance(Credentials::class);
        $this->admin = Admin::factory()->create(['email' => 'owner@shop.test', 'name' => 'Store Owner']);
        $this->actingAs($this->admin, 'admin');
        Storage::fake(config('store.uploads.disk'));
    }

    private function withLogo(): void
    {
        Storage::disk(config('store.uploads.disk'))->put('branding/logo.png', UploadedFile::fake()->image('logo.png', 300, 80)->getContent());
        app(SettingsService::class)->setMany('general', ['logo' => 'branding/logo.png']);
    }

    /** @return list<Email> */
    private function sent(): array
    {
        return collect(app('mail.manager')->mailer('array')->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage())->all();
    }

    public function test_page_has_the_test_box_with_the_admins_address(): void
    {
        $this->get('/admin/settings/mail-server')->assertOk()
            ->assertSee('Send a test email to')
            ->assertSee('value="owner@shop.test"', false);
    }

    public function test_sends_to_any_address_with_the_logo_inside_the_message(): void
    {
        $this->withLogo();
        config(['mail.mailers.smtp.host' => 'smtp.shop.test', 'mail.default' => 'array']);

        $this->post('/admin/settings/credentials/smtp/test', ['to' => 'someone@else.test'])
            ->assertRedirect(route('admin.settings.mail-server.edit').'#mail-test')
            ->assertSessionHas('mail_test', fn ($r) => $r['ok'] === true && $r['to'] === 'someone@else.test');

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $email = $messages[0];
        $this->assertSame('someone@else.test', $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('src="cid:', (string) $email->getHtmlBody());
        $this->assertStringNotContainsString('branding/logo.png"', (string) $email->getHtmlBody());
        $inline = array_filter($email->getAttachments(), fn ($p) => $p->getDisposition() === 'inline');
        $this->assertCount(1, $inline);

        $this->get('/admin/settings/mail-server')->assertSee('Test email sent to someone@else.test');
    }

    public function test_blank_address_falls_back_to_the_signed_in_admin(): void
    {
        config(['mail.mailers.smtp.host' => 'smtp.shop.test', 'mail.default' => 'array']);
        $this->post('/admin/settings/credentials/smtp/test', ['to' => ''])->assertSessionHas('mail_test', fn ($r) => $r['to'] === 'owner@shop.test');
    }

    public function test_a_bad_address_is_refused(): void
    {
        config(['mail.mailers.smtp.host' => 'smtp.shop.test', 'mail.default' => 'array']);
        $this->post('/admin/settings/credentials/smtp/test', ['to' => 'not-an-email'])->assertSessionHasErrors('to');
        $this->assertCount(0, $this->sent());
    }

    public function test_no_mail_server_saved_says_so(): void
    {
        config(['mail.mailers.smtp.host' => null]);
        $this->post('/admin/settings/credentials/smtp/test', ['to' => 'a@b.test'])
            ->assertSessionHas('mail_test', fn ($r) => $r['ok'] === false && $r['reason'] === __('admin_mail.no_host'));
    }

    public function test_a_blocked_port_is_explained_in_plain_words(): void
    {
        // Nothing listens on port 1 here, so the connection is refused for real.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.encryption' => null, 'mail.mailers.smtp.scheme' => null]);

        $this->post('/admin/settings/credentials/smtp/test', ['to' => 'a@b.test'])
            ->assertSessionHas('mail_test', function ($r) {
                return $r['ok'] === false
                    && str_contains($r['reason'], 'Connection refused on port 1')
                    && count($r['fixes']) === 3 && str_contains($r['fixes'][1], 'SMTP_BLOCK = 0')
                    && filled($r['details']);
            });

        $this->get('/admin/settings/mail-server')->assertSee('The test email was not sent')->assertSee('Technical details')->assertSee('SMTP_ALLOWUSER');
    }

    public function test_common_errors_get_their_own_reason(): void
    {
        $gmail = MailErrorExplainer::explain('Failed to authenticate on SMTP server with username "x" using 3 possible authenticators. Authenticator LOGIN returned Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted."', 'smtp.gmail.com', 587, 'tls');
        $this->assertStringContainsString('app password', $gmail['reason']);

        $this->assertStringContainsString('Login failed', MailErrorExplainer::explain('535 Incorrect authentication data', 'mail.shop.test', 587)['reason']);
        $this->assertStringContainsString('“smtp.gmial.com” does not exist', MailErrorExplainer::explain('stream_socket_client(): php_network_getaddresses: getaddrinfo for smtp.gmial.com failed: Name or service not known', 'smtp.gmial.com', 587)['reason']);
        $this->assertStringContainsString('port 465 needs SSL', MailErrorExplainer::explain('stream_socket_enable_crypto(): SSL operation failed with code 1. OpenSSL Error messages: wrong version number', 'mail.shop.test', 465, 'tls')['reason']);
        $this->assertStringContainsString('No answer from mail.shop.test on port 587', MailErrorExplainer::explain('Connection could not be established with host "mail.shop.test:587": stream_socket_client(): Unable to connect (Connection timed out)', 'mail.shop.test', 587)['reason']);
        $this->assertStringContainsString('refused the sender', MailErrorExplainer::explain('Expected response code "250" but got code "550", with message "550 Sender address rejected: not owned by user"', 'mail.shop.test', 587)['reason']);
        $this->assertStringStartsWith('The mail server said: Something odd', MailErrorExplainer::explain('Something odd happened', 'x', 25)['reason']);
    }

    public function test_every_store_email_carries_the_logo_inline(): void
    {
        $this->withLogo();
        Mail::mailer('array')->to('c@shop.test')->send(new CustomerMessage('welcome', ['{name}' => 'Layla']));

        $email = $this->sent()[0];
        $this->assertStringContainsString('src="cid:', (string) $email->getHtmlBody());
        $this->assertCount(1, array_filter($email->getAttachments(), fn ($p) => $p->getDisposition() === 'inline'));
    }

    public function test_without_a_logo_nothing_is_attached(): void
    {
        Mail::mailer('array')->to('c@shop.test')->send(new CustomerMessage('welcome', ['{name}' => 'Layla']));

        $email = $this->sent()[0];
        $this->assertStringNotContainsString('cid:', (string) $email->getHtmlBody());
        $this->assertCount(0, $email->getAttachments());
    }

    public function test_admin_preview_keeps_a_normal_image(): void
    {
        $this->withLogo();
        $html = $this->get('/admin/settings/emails/welcome/preview')->assertOk()->getContent();
        $this->assertStringContainsString('branding/logo.png', $html);
        $this->assertStringNotContainsString('cid:', $html);
    }
}
