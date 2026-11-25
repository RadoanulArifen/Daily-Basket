<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCurrencies();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_currency_list_shows_base_currency(): void
    {
        $this->get('/admin/settings/currencies')->assertOk()->assertSee('AED')->assertSee('USD');
    }

    public function test_a_currency_can_be_added(): void
    {
        $this->post('/admin/settings/currencies', [
            'code' => 'eur',
            'name' => ['en' => 'Euro', 'ar' => 'يورو'],
            'symbol' => 'EUR',
            'decimal_places' => 2,
            'exchange_rate' => '0.235',
            'is_active' => '1',
        ])->assertRedirect('/admin/settings/currencies')->assertSessionHasNoErrors();

        $eur = Currency::where('code', 'EUR')->firstOrFail();
        $this->assertSame('0.23500000', $eur->exchange_rate);
        $this->assertSame('يورو', $eur->getTranslation('name', 'ar'));
        $this->assertFalse($eur->is_base);
    }

    public function test_duplicate_codes_are_rejected(): void
    {
        $this->post('/admin/settings/currencies', [
            'code' => 'USD',
            'name' => ['en' => 'Dollar again'],
            'symbol' => '$',
            'decimal_places' => 2,
            'exchange_rate' => '0.27',
        ])->assertSessionHasErrors('code');
    }

    public function test_base_currency_rate_and_code_cannot_change(): void
    {
        $aed = Currency::where('code', 'AED')->firstOrFail();

        $this->put("/admin/settings/currencies/{$aed->id}", [
            'code' => 'AED',
            'name' => ['en' => 'Dirham', 'ar' => 'درهم'],
            'symbol' => 'AED',
            'decimal_places' => 2,
            'exchange_rate' => '2',
            'is_active' => '1',
        ])->assertSessionHasErrors('exchange_rate');

        $this->put("/admin/settings/currencies/{$aed->id}", [
            'code' => 'AED',
            'name' => ['en' => 'Dirham', 'ar' => 'درهم'],
            'symbol' => 'AED',
            'decimal_places' => 2,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $aed->refresh();
        $this->assertSame('1.00000000', $aed->exchange_rate);
        $this->assertSame('Dirham', $aed->getTranslation('name', 'en'));
        $this->assertTrue($aed->is_active);
    }

    public function test_base_currency_cannot_be_deleted(): void
    {
        $aed = Currency::where('code', 'AED')->firstOrFail();

        $this->delete("/admin/settings/currencies/{$aed->id}")->assertSessionHas('error');
        $this->assertModelExists($aed);
    }

    public function test_other_currencies_can_be_deleted(): void
    {
        $usd = Currency::where('code', 'USD')->firstOrFail();

        $this->delete("/admin/settings/currencies/{$usd->id}")->assertRedirect('/admin/settings/currencies');
        $this->assertModelMissing($usd);
    }
}
