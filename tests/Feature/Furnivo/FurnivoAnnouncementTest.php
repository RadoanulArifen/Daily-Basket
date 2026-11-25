<?php

namespace Tests\Feature\Furnivo;

use App\Models\Admin;
use App\Services\Settings\SettingsService;

class FurnivoAnnouncementTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function announce(bool $on, string $text = 'Complimentary delivery on orders over $5,000'): void
    {
        app(SettingsService::class)->setMany('general', [
            'announcement_enabled' => $on,
            'announcement_text' => ['en' => $text],
        ]);
    }

    public function test_the_announcement_bar_shows_when_it_is_switched_on(): void
    {
        $this->announce(false);
        $this->get('/')->assertOk()->assertDontSee('Complimentary delivery');

        $this->announce(true);
        $this->get('/')->assertOk()->assertSee('Complimentary delivery on orders over $5,000', false);
    }

    public function test_pages_move_down_so_the_bar_hides_nothing(): void
    {
        $this->announce(false);
        $this->get('/products/halo-floor')->assertOk()->assertSee('padding-top: 120px', false);

        $this->announce(true);
        $this->get('/products/halo-floor')->assertOk()->assertSee('padding-top: 160px', false);
    }

    public function test_an_empty_message_shows_no_bar(): void
    {
        $this->announce(true, '');

        $this->get('/')->assertOk()->assertDontSee('fv-announce', false);
    }

    public function test_the_admin_names_the_theme_it_is_running(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/online-store')->assertOk()
            ->assertSee(config('store.themes.furnivo.name'))
            ->assertSee('themes/furnivo/preview.jpg', false);
    }
}
