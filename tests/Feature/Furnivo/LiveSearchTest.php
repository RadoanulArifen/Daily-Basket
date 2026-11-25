<?php

namespace Tests\Feature\Furnivo;

class LiveSearchTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    public function test_typing_returns_a_few_pieces(): void
    {
        $body = $this->getJson('/search/suggest?q=sofa')->assertOk()->json();

        $this->assertNotEmpty($body['results']);
        $this->assertLessThanOrEqual(6, count($body['results']), 'A short list, not a listing page.');
        $this->assertArrayHasKey('url', $body['results'][0]);
        $this->assertArrayHasKey('price', $body['results'][0]);
    }

    public function test_one_or_two_characters_return_nothing_at_all(): void
    {
        $this->getJson('/search/suggest?q=s')->assertOk()->assertJson(['results' => []]);
    }

    public function test_a_misspelling_comes_back_with_a_suggestion(): void
    {
        $body = $this->getJson('/search/suggest?q=sofla')->assertOk()->json();

        $this->assertSame([], $body['results']);
        $this->assertSame('sofa', $body['suggestion']);
    }

    public function test_a_word_that_matches_needs_no_suggestion(): void
    {
        $this->assertNull($this->getJson('/search/suggest?q=sofa')->assertOk()->json('suggestion'));
    }

    public function test_the_plain_form_still_works_without_any_of_this(): void
    {
        // JavaScript off: the form posts to the full results page as before.
        $this->get('/search?q=sofa')->assertOk()->assertSee('Sofa', false);
    }
}
