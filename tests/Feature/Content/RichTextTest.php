<?php

namespace Tests\Feature\Content;

use App\Models\Page;
use App\Models\Product;
use App\Services\Settings\SettingsService;
use App\Support\RichText;
use Tests\Feature\Furnivo\FurnivoTestCase;

/** HTML editor fields: cleaned on save, shown formatted, product texts fall back to store defaults. */
class RichTextTest extends FurnivoTestCase
{
    public function test_unsafe_html_is_removed_but_formatting_is_kept(): void
    {
        $clean = RichText::clean('<p onclick="x()">Hi <strong>bold</strong><script>alert(1)</script> <a href="javascript:alert(1)">x</a> <a href="https://a.com">ok</a></p><img src=x onerror=alert(1)><h1>Title</h1>');

        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<a href="https://a.com" rel="noopener">ok</a>', $clean);
        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        foreach (['script', 'onclick', 'onerror', 'javascript:', '<img'] as $bad) {
            $this->assertStringNotContainsString($bad, $clean);
        }
        $this->assertSame('<p>Line one<br>'."\n".'two</p><p>Next</p>', RichText::clean("Line one\ntwo\n\nNext"));
    }

    public function test_models_clean_their_editor_fields_on_save(): void
    {
        $page = Page::create(['slug' => 'x', 'title' => ['en' => 'X'], 'body' => ['en' => '<h2>Q</h2><p>A<script>bad()</script></p>']]);
        $this->assertSame('<h2>Q</h2><p>A</p>', $page->fresh()->getTranslation('body', 'en'));
    }

    public function test_product_page_uses_the_products_texts_then_the_store_defaults(): void
    {
        $this->seedFurnivo();
        $product = Product::where('slug', 'nord-accent')->first();
        $product->forceFill([])->setTranslation('short_description', 'en', '<p>Own <strong>short</strong> text</p>')
            ->setTranslation('dimensions', 'en', '<ul><li>W 80 cm</li></ul>');
        $product->delivery_returns = null;
        $product->trust_badges = ['en' => "Ships in 3 days\nFree returns"];
        $product->save();
        app(SettingsService::class)->setMany('product_page', ['delivery_returns' => ['en' => '<p>Store-wide delivery words</p>']]);

        $this->get('/products/nord-accent')->assertOk()
            ->assertSee('<p>Own <strong>short</strong> text</p>', false)
            ->assertSee('<li>W 80 cm</li>', false)
            ->assertSee('Store-wide delivery words')
            ->assertSee('Ships in 3 days')->assertDontSee('5-year warranty');
    }

    public function test_content_pages_have_the_design_parts_and_a_centred_column(): void
    {
        $this->seedFurnivo();

        $this->get('/pages/about')->assertOk()->assertSeeInOrder(['Our Brand', 'We don&#039;t settle for mediocre.', 'For decades, Furnivo'], false);
        $this->get('/pages/careers')->assertOk()->assertSeeInOrder(['Careers at Furnivo', 'Build the exceptional.', 'Senior Furniture Designer', 'Design Studio · London · Full-time'], false);
        $this->get('/pages/warranty-policy')->assertOk()
            ->assertSeeInOrder(['Assurance', 'Warranty Policy', 'Every Furnivo piece', 'What is covered', 'Last updated July 2026'], false)
            ->assertSee('max-width: 760px; margin: 0px auto;', false);
        $this->get('/pages/faqs')->assertOk()->assertSeeInOrder(['Help centre', 'Frequently asked questions'], false);
    }
}
