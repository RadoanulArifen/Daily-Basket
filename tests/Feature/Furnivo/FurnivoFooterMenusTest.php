<?php

namespace Tests\Feature\Furnivo;

use App\Models\Menu;

class FurnivoFooterMenusTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
        $this->seed(\Database\Seeders\FurnivoContentSeeder::class);
    }

    public function test_the_footer_links_can_be_changed_in_the_admin(): void
    {
        $this->get('/')->assertOk()->assertSee('Contact Us');

        $item = Menu::query()->where('handle', 'footer-support')->firstOrFail()
            ->items()->orderBy('sort_order')->firstOrFail();
        $item->update(['label' => ['en' => 'Talk to us']]);

        $this->get('/')->assertOk()->assertSee('Talk to us')->assertDontSee('Contact Us');
    }

    public function test_the_designs_links_are_used_when_a_menu_is_empty(): void
    {
        Menu::query()->where('handle', 'footer-discover')->firstOrFail()->items()->delete();

        // Nothing disappears: the theme falls back to its own list.
        $this->get('/')->assertOk()->assertSee('About us')->assertSee('Careers');
    }

    public function test_the_seeder_leaves_the_owners_own_links_alone(): void
    {
        $menu = Menu::query()->where('handle', 'footer-support')->firstOrFail();
        $menu->items()->delete();
        $menu->items()->create(['label' => ['en' => 'Only mine'], 'url' => '/pages/contact', 'sort_order' => 0]);

        $this->seed(\Database\Seeders\FurnivoContentSeeder::class);

        $this->assertSame(1, $menu->items()->count());
        $this->get('/')->assertOk()->assertSee('Only mine');
    }
}
