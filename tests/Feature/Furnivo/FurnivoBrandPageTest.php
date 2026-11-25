<?php

namespace Tests\Feature\Furnivo;

use App\Models\Brand;

class FurnivoBrandPageTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_the_brand_page_follows_the_design(): void
    {
        $this->get('/brands/alhuzaifa')->assertOk()->assertSeeInOrder([
            'Home', 'Al Huzaifa Furniture',
            'Maison ·', 'London, UK',
            'Handcrafted luxury furniture since 1976.',
            'Origin', 'London, UK', 'In the collection', '15', 'pieces', 'Availability',
            'The Collection', 'Al Huzaifa Furniture', 'pieces', 'of', '15',
            'All', 'Seating', 'Tables',
            'What clients say', 'Al Huzaifa Furniture', 'reviews', 'Sort', 'Most recent',
        ], false);
    }

    public function test_type_tabs_narrow_the_collection(): void
    {
        $all = $this->get('/brands/alhuzaifa')->assertOk();
        $this->assertStringContainsString('?type=seating', $all->getContent());

        $seating = $this->get('/brands/alhuzaifa?type=seating')->assertOk();
        $seating->assertSee('Seating <span>', false)->assertSee('Lucca 3-Seater Sofa');
        $seating->assertDontSee('Monolith Dining Table');   // a table, not seating
    }

    public function test_pieces_are_shown_eight_to_a_page(): void
    {
        $page = $this->get('/brands/alhuzaifa')->assertOk();

        $links = preg_match_all('#href="[^"]*/products/[^"]*"#', $page->getContent(), $m);
        $this->assertSame(8, count(array_unique($m[0])));
        $this->assertStringContainsString('page=2', $page->getContent());
    }

    public function test_reviews_come_from_the_brands_own_pieces(): void
    {
        $page = $this->get('/brands/alhuzaifa')->assertOk()->getContent();

        // Three quotes, as in the design, with a real average.
        $this->assertSame(3, substr_count($page, '”</p>'));
        $this->assertMatchesRegularExpression('/<span>[45]\.\d<\/span>/', $page);

        $byRating = $this->get('/brands/alhuzaifa?reviews=rating')->assertOk();
        $byRating->assertSee('Highest rated');
    }

    public function test_a_brand_with_no_reviews_still_works(): void
    {
        $brand = Brand::query()->where('slug', 'nove')->firstOrFail();
        \App\Models\ProductReview::query()->whereIn('product_id',
            \App\Models\Product::query()->where('brand_id', $brand->id)->pluck('id'))->delete();

        $this->get('/brands/nove')->assertOk()->assertDontSee('What clients say');
    }
}
