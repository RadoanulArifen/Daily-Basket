<?php

namespace Tests\Feature\Furnivo;

use App\Models\Brand;

class FurnivoListingTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    /** The number in the "N pieces" line. */
    private function total(string $url): int
    {
        preg_match('/>(\d+)<\/span> pieces/', $this->get($url)->assertOk()->getContent(), $m);

        return (int) ($m[1] ?? -1);
    }

    public function test_a_type_shows_the_design_listing(): void
    {
        $this->get('/collections/sofas-sectionals')->assertOk()->assertSeeInOrder([
            'Home', 'Seating', 'Sofas &amp; Sectionals',
            'Collection', 'Sofas &amp; Sectionals', 'Sofas, sectionals and chairs', 'pieces',
            'Filter', 'Clear all', 'Categories', 'All',
            'Brand', 'Dimensions', 'Frame', 'Finish', 'Upholstery',
            'Colour &amp; Material', 'Natural Wood', 'Price', 'Any price', 'Availability', 'Made to Order', 'Offers', 'On offer',
            'Search results for', 'Sort — Featured',
        ], false);
    }

    public function test_each_filter_narrows_the_results(): void
    {
        $all = $this->total('/collections/sofas-sectionals');
        $this->assertSame(10, $all);

        $this->assertSame(2, $this->total('/collections/sofas-sectionals?frame%5B%5D=Oak'));
        $this->assertSame(2, $this->total('/collections/sofas-sectionals?colour%5B%5D=leather'));
        $this->assertSame(4, $this->total('/collections/sofas-sectionals?availability=made_to_order'));
        $this->assertLessThan($all, $this->total('/collections/sofas-sectionals?band=lo'));
        $this->assertSame(6, $this->total('/collections/seating?upholstery%5B%5D=Bouclé'));
    }

    public function test_filters_combine_and_can_be_cleared(): void
    {
        $both = $this->total('/collections/seating?frame%5B%5D=Oak&availability=made_to_order');
        $this->assertLessThanOrEqual($this->total('/collections/seating?frame%5B%5D=Oak'), $both);

        // "Clear all" points back at the plain listing.
        $this->get('/collections/seating?frame%5B%5D=Oak')->assertOk()->assertSee('href="'.url('/collections/seating').'"', false);
    }

    public function test_brand_filter_uses_real_brands(): void
    {
        $brand = Brand::query()->where('name->en', 'Loom Collection')->firstOrFail();

        $page = $this->get('/collections/seating?brand%5B%5D='.$brand->id)->assertOk();

        $page->assertSee('Loom Collection');
        $this->assertGreaterThan(0, $this->total('/collections/seating?brand%5B%5D='.$brand->id));
        $this->assertLessThan($this->total('/collections/seating'), $this->total('/collections/seating?brand%5B%5D='.$brand->id));
    }

    public function test_pages_follow_the_design_with_nine_per_page(): void
    {
        // 10 pieces in this type, 9 to a page as in the design.
        $page = $this->get('/collections/sofas-sectionals')->assertOk();
        $this->assertStringContainsString('page=2', $page->getContent());

        $this->get('/collections/sofas-sectionals?page=2')->assertOk()->assertSee('Search results for', false);
    }

    public function test_filtered_pages_are_kept_out_of_search_engines(): void
    {
        $this->get('/collections/seating?frame%5B%5D=Oak')->assertOk()->assertSee('noindex', false);
        $this->get('/collections/sofas-sectionals')->assertOk()->assertDontSee('noindex', false);
    }
}
