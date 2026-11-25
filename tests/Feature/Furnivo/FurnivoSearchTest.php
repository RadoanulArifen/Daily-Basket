<?php

namespace Tests\Feature\Furnivo;

class FurnivoSearchTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function total(string $url): int
    {
        preg_match('/>(\d+)<\/span> pieces/', $this->get($url)->assertOk()->getContent(), $m);

        return (int) ($m[1] ?? -1);
    }

    public function test_results_use_the_designs_listing(): void
    {
        $this->get('/search?q=chair')->assertOk()->assertSeeInOrder([
            'Home', 'Search', 'Search results', '“chair”', 'pieces',
            'Filter', 'Clear all', 'Categories', 'Brand',
            'Search results for', '“chair”', 'Sort — Featured',
        ], false);

        $this->assertSame(9, $this->total('/search?q=chair'));
    }

    public function test_filters_narrow_the_results_and_keep_the_term(): void
    {
        $this->assertSame(2, $this->total('/search?q=chair&frame%5B%5D=Oak'));

        // Every filter link keeps the search term.
        $page = $this->get('/search?q=chair')->assertOk()->getContent();
        $this->assertStringContainsString('q=chair', $page);
        $this->assertStringNotContainsString('href="'.url('/search').'?frame', $page);
    }

    public function test_nothing_found_offers_a_way_on(): void
    {
        $this->get('/search?q=zzzznothing')->assertOk()
            ->assertSee('No results for “zzzznothing”', false)
            ->assertSee('Shop all')
            ->assertSee('Popular right now')
            ->assertDontSee('Clear all');
    }

    public function test_a_short_or_empty_term_asks_again(): void
    {
        $this->get('/search')->assertOk()->assertSee('Search furniture, lighting, brands', false);
        $this->get('/search?q=a')->assertOk()->assertDontSee('Clear all');
    }

    public function test_result_pages_are_kept_out_of_search_engines(): void
    {
        $this->get('/search?q=chair')->assertOk()->assertSee('noindex', false);
    }
}
