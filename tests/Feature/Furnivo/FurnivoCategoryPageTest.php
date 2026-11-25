<?php

namespace Tests\Feature\Furnivo;

class FurnivoCategoryPageTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_a_category_shows_the_design_landing_page(): void
    {
        $this->get('/collections/seating')->assertOk()->assertSeeInOrder([
            'Home', 'Seating',
            'Collection ·', '27', 'pieces',
            'Sofas, sectionals and chairs',
            'Browse', 'Shop', 'Seating', 'by type', 'View all',
            'Sofas &amp; Sectionals', '10', 'pieces',
            'Armchairs &amp; Lounge Chairs', '5',
            'Highlights from', 'Seating', 'Lucca 3-Seater Sofa',
        ], false);
    }

    public function test_subcategory_tiles_link_to_their_listing(): void
    {
        $this->get('/collections/seating')->assertOk()->assertSee('/collections/sofas-sectionals', false);

        // A sub-category is a listing, not another landing page.
        $this->get('/collections/sofas-sectionals')->assertOk()->assertDontSee('Highlights from');
    }

    public function test_filtered_and_sorted_views_use_the_listing(): void
    {
        $this->get('/collections/seating?sort=price_asc')->assertOk()->assertDontSee('Highlights from');
        $this->get('/collections/new-in')->assertOk()->assertDontSee('Highlights from');
        $this->get('/shop')->assertOk()->assertDontSee('Highlights from');
    }

    public function test_the_hero_runs_under_the_header_and_the_category_is_marked_in_the_menu(): void
    {
        $page = $this->get('/collections/seating')->assertOk()->getContent();

        $this->assertStringContainsString('data-nav-theme="dark"', $page);
        $this->assertStringContainsString('overHero: true', $page);
        $this->assertSame(1, substr_count($page, 'font-weight: 700; transition: color 500ms;'), 'Only the browsed category is bold.');
    }
}
