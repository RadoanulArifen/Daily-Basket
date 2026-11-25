<?php

namespace Tests\Feature\Furnivo;

class FurnivoErrorPageTest extends FurnivoTestCase
{
    public function test_storefront_404_uses_the_theme_with_a_way_onward(): void
    {
        $this->seedFurnivo();

        $response = $this->get('/no-such-page')->assertNotFound();

        $response->assertSee('Error 404')
            ->assertSee('We can’t find that page', false)
            ->assertSee('Go to the home page')
            ->assertSee('Shop all')
            ->assertSee('Popular right now')
            ->assertSee('Search furniture, lighting, brands', false)
            ->assertSee('Skip to content')          // the theme header
            ->assertSee('Join the List', false);    // the theme footer

        $this->assertSame(1, substr_count($response->getContent(), '<!DOCTYPE'), 'The page must render once.');
        $this->assertStringContainsString('noindex', $response->getContent());
    }

    public function test_admin_404_stays_plain(): void
    {
        $response = $this->get('/'.config('store.admin_path').'/no-such-page')->assertNotFound();

        $response->assertSee('We can’t find that page', false)->assertDontSee('Popular right now');
        $this->assertSame(1, substr_count($response->getContent(), '<!DOCTYPE'));
    }
}
