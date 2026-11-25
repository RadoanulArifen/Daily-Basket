<?php

namespace Tests\Feature\Freshnest;

use App\Models\Admin;
use App\Models\Banner;

/** A theme can add its own banner places (Content → Banners → Where); other themes are unchanged. */
class ThemeBannerPlacementsTest extends FreshnestTestCase
{
    public function test_freshnest_adds_its_places_with_names(): void
    {
        $this->assertContains('weekend', Banner::placements());
        $this->assertContains('hero', Banner::placements());
        $this->assertStringStartsWith('Home: big offer with countdown', Banner::placementLabel('weekend'));
        $this->assertSame('Top of home page', Banner::placementLabel('hero'));
    }

    public function test_other_themes_keep_the_two_places(): void
    {
        config(['store.theme' => 'furnivo']);
        $this->assertSame(['hero', 'promo'], Banner::placements());
    }

    public function test_the_admin_form_offers_and_saves_a_theme_place(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class]);
        $admin = Admin::factory()->create();
        $path = config('store.admin_path', 'admin');

        $this->actingAs($admin, 'admin')->get(route('admin.banners.create'))->assertOk()->assertSee('Home: big offer with countdown');
        $this->actingAs($admin, 'admin')->post(route('admin.banners.store'), [
            'placement' => 'weekend', 'title' => ['en' => 'Weekend deal'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Banner::query()->where('placement', 'weekend')->exists());
    }

    public function test_arabic_names_exist_for_every_theme_place(): void
    {
        foreach (\App\Services\Theme\ThemeSettings::BANNER_PLACEMENTS['freshnest'] as $placement) {
            $this->assertTrue(\Illuminate\Support\Facades\Lang::has("admin.banners.theme_placements.freshnest.{$placement}", 'ar', false), $placement);
        }
    }
}
