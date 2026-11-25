<?php

namespace Tests\Feature\Freshnest;

use App\Models\Currency;
use Database\Seeders\FreshnestSeeder;

/** US dollars as base; language and currency pickers only when there is a choice; Arabic is right-to-left. */
class FreshnestLanguageCurrencyTest extends FreshnestTestCase
{
    public function test_dollar_is_the_base_currency_and_others_are_priced_against_it(): void
    {
        $this->seedFreshnest();

        $this->assertSame(['USD'], Currency::query()->where('is_base', true)->pluck('code')->all());
        $this->assertSame('3.6725', rtrim(rtrim((string) Currency::query()->where('code', 'AED')->value('exchange_rate'), '0'), '.'));
        $this->assertFalse((bool) Currency::query()->where('code', 'EUR')->value('is_active'));
    }

    public function test_older_store_with_the_dirham_as_base_is_fixed(): void
    {
        $this->seedFreshnest();
        Currency::query()->update(['is_base' => false]);
        Currency::query()->where('code', 'AED')->update(['is_base' => true]);

        $this->seed(\Database\Seeders\FreshnestHomepageSeeder::class);
        $this->assertSame(['USD'], Currency::query()->where('is_base', true)->pluck('code')->all());
    }

    public function test_no_pickers_with_one_language_and_one_currency(): void
    {
        $this->seedFreshnest();

        $this->get('/shop')->assertOk()->assertDontSee('name="currency"', false)->assertDontSee('العربية');
    }

    public function test_currency_picker_appears_when_a_second_currency_is_active(): void
    {
        $this->seedFreshnest();
        Currency::query()->where('code', 'EUR')->update(['is_active' => true]);
        app(\App\Services\Currency\CurrencyService::class)->flush();

        $this->get('/shop')->assertOk()->assertSee('name="currency"', false)->assertSee('<option value="EUR"', false);
    }

    public function test_arabic_adds_the_language_picker_and_right_to_left(): void
    {
        config(['store.all_locales' => ['en' => ['name' => 'English', 'dir' => 'ltr'], 'ar' => ['name' => 'العربية', 'dir' => 'rtl']]]);
        $this->seedFreshnest();
        settings()->setMany('general', ['locales' => ['en', 'ar']]);

        $this->get('/shop')->assertOk()->assertSee('العربية')->assertSee('dir="ltr"', false);
        $this->get('/ar/shop')->assertOk()->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false);
    }
}
