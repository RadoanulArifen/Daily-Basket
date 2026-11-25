<?php

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);
    }

    public function test_english_home_page_is_left_to_right(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Demo Store')
            ->assertSee('hreflang="ar"', false);
    }

    public function test_arabic_home_page_is_right_to_left(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('متجر تجريبي');
    }

    public function test_arabic_menu_links_keep_the_language_prefix(): void
    {
        $this->get('/ar')->assertSee(url('/ar/collections/living'), false);
    }

    public function test_language_switch_points_to_the_same_page(): void
    {
        $this->get('/login')->assertSee(url('/ar/login'), false);
        $this->get('/ar/login')->assertSee(url('/login'), false);
    }

    public function test_unknown_pages_show_the_404_page(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('We can’t find that page');
        $this->get('/ar/this-page-does-not-exist')->assertNotFound()->assertSee('لم نعثر على هذه الصفحة');
    }

    public function test_currency_can_be_switched_to_an_active_currency_only(): void
    {
        $this->from('/')->post('/currency', ['currency' => 'USD'])->assertRedirect('/');
        $this->assertSame('USD', session('currency'));

        // SAR is seeded as inactive.
        $this->from('/')->post('/currency', ['currency' => 'SAR'])->assertSessionHasErrors('currency');
        $this->assertSame('USD', session('currency'));
    }

    public function test_prices_are_shown_in_the_selected_currency(): void
    {
        $this->assertSame("AED\u{00A0}100.00", price('100'));

        session(['currency' => 'USD']);
        $this->assertSame("USD\u{00A0}27.23", price('100'));

        app()->setLocale('ar');
        session(['currency' => 'AED']);
        $this->assertSame("100.00\u{00A0}د.إ", price('100'));
    }
}
