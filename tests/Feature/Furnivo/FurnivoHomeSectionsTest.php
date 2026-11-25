<?php

namespace Tests\Feature\Furnivo;

use App\Services\Settings\SettingsService;
use App\Services\Theme\ThemeSettings;

class FurnivoHomeSectionsTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_customize_lists_every_section_with_a_name_design_ones_ticked(): void
    {
        $sections = app(ThemeSettings::class)->sections();

        $this->assertSame(ThemeSettings::keys(), array_column($sections, 'key'));
        $this->assertSame(['hero', 'brands', 'categories', 'rails'], app(ThemeSettings::class)->visibleSections());
        foreach (ThemeSettings::labels() as $key => $label) {
            $this->assertNotSame('', $label, $key);
            $this->assertStringNotContainsString('admin.online', $label, $key);
        }
        $this->assertSame('Brand logos', ThemeSettings::labels()['brands']);
    }

    public function test_the_design_order_is_drawn(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $positions = array_map(fn ($needle) => strpos($html, $needle), ['aria-roledescription="carousel"', 'fx-brand-marquee', 'fx-category-showcase', 'id="rail-featured"']);
        $this->assertNotContains(false, $positions);
        $sorted = $positions; sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    public function test_every_optional_section_renders_when_ticked(): void
    {
        app(SettingsService::class)->setMany('theme', [
            'sections' => collect(ThemeSettings::keys())->map(fn ($k) => ['key' => $k, 'visible' => true])->all(),
        ]);

        $this->get('/')->assertOk();
    }

    public function test_an_old_saved_list_keeps_extras_hidden(): void
    {
        // Saved before brands/rails existed: unknown keys dropped, new design ones shown, extras hidden.
        app(SettingsService::class)->setMany('theme', ['sections' => [['key' => 'hero', 'visible' => true], ['key' => 'categories', 'visible' => true]]]);

        $this->assertSame(['hero', 'categories', 'brands', 'rails'], app(ThemeSettings::class)->visibleSections());
    }
}
