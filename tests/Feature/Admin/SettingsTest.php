<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCurrencies();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_settings_pages_load(): void
    {
        foreach (['/admin', '/admin/settings', '/admin/settings/general', '/admin/settings/tax', '/admin/settings/checkout', '/admin/account'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_general_settings_are_saved_in_both_languages(): void
    {
        $this->put('/admin/settings/general', [
            'store_name' => ['en' => 'Oud Corner', 'ar' => 'ركن العود'],
            'tagline' => ['en' => 'Fragrance for every home', 'ar' => ''],
            'store_email' => 'hello@oudcorner.ae',
            'trn' => '100123456700003',
            'announcement_enabled' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $settings = app(SettingsService::class);
        $settings->flush();

        $this->assertSame('Oud Corner', $settings->trans('general.store_name', 'en'));
        $this->assertSame('ركن العود', $settings->trans('general.store_name', 'ar'));
        // An empty Arabic value falls back to English.
        $this->assertSame('Fragrance for every home', $settings->trans('general.tagline', 'ar'));
        $this->assertTrue($settings->bool('general.announcement_enabled'));
    }

    public function test_logo_can_be_uploaded_and_removed(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP GD extension is needed to create a test image.');
        }

        Storage::fake('public');
        $base = ['store_name' => ['en' => 'Demo Store'], 'store_email' => 'hello@example.com'];

        $this->put('/admin/settings/general', $base + ['logo' => UploadedFile::fake()->image('logo.png', 400, 120)])
            ->assertSessionHasNoErrors();

        $settings = app(SettingsService::class);
        $settings->flush();
        $path = $settings->get('general.logo');
        Storage::disk('public')->assertExists($path);

        $this->put('/admin/settings/general', $base + ['remove_logo' => '1'])->assertSessionHasNoErrors();

        $settings->flush();
        $this->assertNull($settings->get('general.logo'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_invalid_general_settings_are_rejected(): void
    {
        $this->put('/admin/settings/general', [
            'store_name' => ['en' => '', 'ar' => 'متجر'],
            'store_email' => 'not-an-email',
            'trn' => '12345',
        ])->assertSessionHasErrors(['store_name.en', 'store_email', 'trn']);
    }

    public function test_tax_settings_are_saved_as_exact_strings(): void
    {
        $this->put('/admin/settings/tax', [
            'enabled' => '1',
            'rate' => '5',
            'label' => ['en' => 'VAT', 'ar' => 'ضريبة القيمة المضافة'],
            'prices_include_tax' => '1',
        ])->assertSessionHasNoErrors();

        $settings = app(SettingsService::class);
        $settings->flush();

        $this->assertSame('5.00', $settings->get('tax.rate'));
        $this->assertTrue($settings->bool('tax.prices_include_tax'));
        $this->assertNotNull($settings->get('tax.reviewed_at'));
    }

    public function test_tax_rate_must_be_a_valid_percentage(): void
    {
        $this->put('/admin/settings/tax', [
            'enabled' => '1',
            'rate' => '150',
            'label' => ['en' => 'VAT'],
        ])->assertSessionHasErrors('rate');
    }

    public function test_checkout_toggles_can_be_switched_off(): void
    {
        $this->put('/admin/settings/checkout', [
            'guest_checkout' => '0',
            'require_phone' => '1',
        ])->assertSessionHasNoErrors();

        $settings = app(SettingsService::class);
        $settings->flush();

        $this->assertFalse($settings->bool('checkout.guest_checkout'));
        $this->assertTrue($settings->bool('checkout.require_phone'));
        $this->assertFalse($settings->bool('checkout.order_notes'));
    }

    public function test_admin_can_change_password_with_current_password(): void
    {
        $this->put('/admin/account/password', [
            'current_password' => 'wrong',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->put('/admin/account/password', [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasNoErrors();
    }
}
