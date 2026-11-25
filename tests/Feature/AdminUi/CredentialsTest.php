<?php

namespace Tests\Feature\AdminUi;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Settings\Credentials;
use App\Services\Shipping\ShippingService;
use App\Support\Money;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Storefront\StorefrontTestCase;

class CredentialsTest extends StorefrontTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.key' => null, 'services.stripe.secret' => null, 'services.stripe.webhook_secret' => null,
            'services.aramex.username' => null, 'services.aramex.password' => null, 'services.aramex.account_number' => null, 'services.aramex.account_pin' => null]);
        app()->forgetInstance(Credentials::class);
        app(Credentials::class)->apply();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_stripe_keys_are_saved_encrypted_masked_and_applied(): void
    {
        $this->put('/admin/settings/credentials/stripe', ['key' => 'pk_test_abc123', 'secret' => 'sk_test_FAKE9876', 'webhook_secret' => 'whsec_hook5555'])->assertSessionHas('success');

        $stored = Setting::query()->where('group', 'credentials')->where('key', 'stripe_secret')->value('value');
        $this->assertStringNotContainsString('sk_test_FAKE9876', (string) $stored);           // encrypted at rest
        $this->assertSame('sk_test_FAKE9876', config('services.stripe.secret'));           // used by the gateway

        // A fresh request (new boot) decrypts it again.
        app()->forgetInstance(Credentials::class);
        config(['services.stripe.secret' => null]);
        app(Credentials::class)->apply();
        $this->assertSame('sk_test_FAKE9876', config('services.stripe.secret'));

        $page = $this->get('/admin/settings/payments')->assertOk();
        $page->assertSee('••••9876')->assertDontSee('sk_test_FAKE9876')->assertSee('pk_test_abc123');   // publishable key isn't secret

        // Empty fields keep the saved values.
        $this->put('/admin/settings/credentials/stripe', ['key' => '', 'secret' => '', 'webhook_secret' => '']);
        $this->assertSame('sk_test_FAKE9876', config('services.stripe.secret'));

        // The activity log records the change, never the value.
        $this->assertFalse(AuditLog::query()->where('changes', 'like', '%secret9876%')->exists());
        $this->assertTrue(AuditLog::query()->where('subject_type', 'credentials')->exists());
    }

    public function test_key_format_and_matching_modes(): void
    {
        $this->put('/admin/settings/credentials/stripe', ['secret' => 'not-a-key'])->assertSessionHasErrors('secret');
        $this->put('/admin/settings/credentials/stripe', ['key' => 'pk_live_abc', 'secret' => 'sk_test_abc'])->assertSessionHasErrors('secret');
    }

    public function test_env_values_win_and_are_locked(): void
    {
        config(['services.stripe.secret' => 'sk_live_FAKEenv1']);
        app()->forgetInstance(Credentials::class);
        app(Credentials::class)->apply();

        $this->put('/admin/settings/credentials/stripe', ['secret' => 'sk_live_FAKEoth2']);
        $this->assertSame('sk_live_FAKEenv1', config('services.stripe.secret'));
        $this->get('/admin/settings/payments')->assertSee('Set on the server');
    }

    public function test_stripe_test_connection(): void
    {
        $this->put('/admin/settings/credentials/stripe', ['secret' => 'sk_test_ok1234']);
        Http::fake(['api.stripe.com/v1/balance' => Http::sequence()->push(['object' => 'balance'])->push(['error' => ['message' => 'Invalid API Key provided']], 401)]);

        $this->post('/admin/settings/credentials/stripe/test')->assertSessionHas('success');
        $this->post('/admin/settings/credentials/stripe/test')->assertSessionHas('error', 'Invalid API Key provided');
    }

    public function test_aramex_must_pass_a_test_before_showing_at_checkout(): void
    {
        $this->put('/admin/settings/credentials/aramex', ['username' => 'shop@example.ae', 'password' => 'pw', 'account_number' => '123456', 'account_pin' => '4321',
            'account_entity' => 'DXB', 'account_country' => 'AE', 'mode' => 'test', 'origin_city' => 'Dubai', 'product_type' => 'onp'])->assertSessionHas('success');

        $this->put('/admin/settings/shipping/aramex', ['enabled' => '1'])->assertSessionHas('error');   // not tested yet
        $this->assertSame([], array_keys(array_diff_key(app(ShippingService::class)->rates(Money::of('100', 'AED'), 'sharjah', 800), ['standard' => 1])));

        Http::fake(['ws.dev.aramex.net/*' => Http::sequence()
            ->push(['HasErrors' => true, 'Notifications' => [['Code' => 'ERR01', 'Message' => 'Invalid username or password']]])
            ->push(['HasErrors' => false, 'TotalAmount' => ['CurrencyCode' => 'AED', 'Value' => 22.5]])
            ->push(['HasErrors' => false, 'TotalAmount' => ['CurrencyCode' => 'AED', 'Value' => 31]])]);

        $this->post('/admin/settings/credentials/aramex/test')->assertSessionHas('error', fn ($m) => str_contains($m, 'Invalid username'));
        $this->post('/admin/settings/credentials/aramex/test')->assertSessionHas('success', fn ($m) => str_contains($m, '22.50'));
        $this->put('/admin/settings/shipping/aramex', ['enabled' => '1'])->assertSessionHas('success');

        $rates = app(ShippingService::class)->rates(Money::of('100', 'AED'), 'sharjah', 800);
        $this->assertSame('31.00', $rates['aramex']->price->toDecimal());
        $this->assertArrayHasKey('standard', $rates);
        Http::assertSent(fn ($r) => ($r['DestinationAddress']['City'] ?? null) === 'Sharjah' && $r['ShipmentDetails']['ProductType'] === 'ONP' && $r['ClientInfo']['AccountNumber'] === '123456');

        // Changing details switches Aramex off until tested again.
        $this->put('/admin/settings/credentials/aramex', ['password' => 'new', 'mode' => 'test', 'origin_city' => 'Dubai', 'product_type' => 'ONP']);
        $this->assertFalse(settings()->bool('shipping.aramex_enabled'));
    }

    public function test_aramex_failure_never_breaks_checkout_options(): void
    {
        foreach (['username' => 'u', 'password' => 'p', 'account_number' => '1', 'account_pin' => '2', 'account_entity' => 'DXB'] as $k => $v) {
            config(["services.aramex.{$k}" => $v]);
        }
        settings()->setMany('shipping', ['aramex_tested_at' => now()->toIso8601String(), 'aramex_enabled' => true]);
        Http::fake(['ws.dev.aramex.net/*' => Http::response('down', 503)]);

        $rates = app(ShippingService::class)->rates(Money::of('100', 'AED'), 'dubai', 500);
        $this->assertArrayNotHasKey('aramex', $rates);
        $this->assertArrayHasKey('standard', $rates);
    }

    public function test_only_owners_manage_credentials(): void
    {
        $this->actingAs(Admin::factory()->create(['role' => 'manager']), 'admin');
        $this->put('/admin/settings/credentials/stripe', ['secret' => 'sk_test_x'])->assertForbidden();
        $this->post('/admin/settings/credentials/aramex/test')->assertForbidden();
    }
}
