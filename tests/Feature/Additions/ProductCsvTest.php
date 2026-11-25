<?php

namespace Tests\Feature\Additions;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Storefront\StorefrontTestCase;

class ProductCsvTest extends StorefrontTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    private function csv(array $rows, string $delimiter = ','): UploadedFile
    {
        $headers = \App\Services\Catalog\ProductCsv::headers();
        $lines = [implode($delimiter, $headers)];
        foreach ($rows as $row) {
            $full = array_merge(array_fill_keys($headers, ''), $row);
            $lines[] = implode($delimiter, array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $full));
        }

        return UploadedFile::fake()->createWithContent('products.csv', "\xEF\xBB\xBF".implode("\n", $lines));
    }

    /** Upload, check, then import; returns the check page response. */
    private function import(UploadedFile $file, bool $confirm = true)
    {
        $check = $this->post('/admin/products/import/check', ['file' => $file]);

        if ($confirm && preg_match('/name="token" value="([^"]+)"/', $check->getContent(), $m)) {
            $this->post('/admin/products/import', ['token' => $m[1]])->assertRedirect('/admin/products');
        }

        return $check;
    }

    public function test_export_lists_every_variant_with_arabic_intact(): void
    {
        $this->category('cushions');
        $this->sizedProduct('Woven Cushion')->categories()->attach(\App\Models\Category::first());
        Product::first()->update(['name' => ['en' => 'Woven Cushion', 'ar' => 'وسادة منسوجة']]);

        $csv = $this->get('/admin/products/export')->assertOk()->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $rows = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));
        $this->assertSame('handle', $rows[0][0]);
        $this->assertCount(3, $rows); // header + 2 variants
        $this->assertContains('وسادة منسوجة', $rows[1]);
        $this->assertContains('cushions', $rows[1]);
        $this->assertContains('Large', $rows[2]);
    }

    public function test_import_creates_products_with_options(): void
    {
        $this->category('cushions');

        $check = $this->import($this->csv([
            ['handle' => 'palm-cushion', 'name_en' => 'Palm Cushion', 'name_ar' => 'وسادة', 'status' => 'active', 'categories' => 'cushions',
             'option1_name_en' => 'Colour', 'option1_value_en' => 'Green', 'sku' => 'PC-G', 'price' => '120.00', 'stock' => '8'],
            ['handle' => 'palm-cushion', 'option1_value_en' => 'Sand', 'sku' => 'PC-S', 'price' => '125.00', 'stock' => '3'],
        ]));

        $check->assertSee('File checked')->assertSee('Import now');
        $product = Product::where('slug', 'palm-cushion')->with('variants.optionValues')->firstOrFail();
        $this->assertSame('وسادة', $product->getTranslation('name', 'ar'));
        $this->assertCount(2, $product->variants);
        $this->assertSame('Sand', $product->variants[1]->title('en'));
        $this->assertSame(3, $product->variants[1]->stock_quantity);
        $this->assertSame(1, $product->categories()->count());
    }

    public function test_round_trip_updates_the_same_variants_and_sets_stock(): void
    {
        $product = $this->sizedProduct('Woven Cushion');
        $small = $product->variants()->orderBy('position')->first();
        $ids = $product->variants()->pluck('id')->sort()->values()->all();

        $csv = $this->get('/admin/products/export')->streamedContent();
        $rows = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));
        $headers = array_shift($rows);
        $rows = array_map(fn ($r) => array_combine($headers, $r), $rows);
        $rows[0]['price'] = '150.00';     // Small: new price
        $rows[0]['stock'] = '20';         // new total
        $rows[1]['stock'] = '';           // Large: keep current stock

        $this->import($this->csv($rows));

        $this->assertSame($ids, $product->variants()->pluck('id')->sort()->values()->all()); // same variants, not recreated
        $this->assertSame('150.00', $small->fresh()->price->toDecimal());
        $this->assertSame(20, $small->fresh()->stock_quantity);
        $this->assertSame(0, $product->variants()->orderBy('position')->skip(1)->first()->stock_quantity);
        $this->assertSame(1, Product::count());
    }

    public function test_problems_are_reported_by_row_and_nothing_is_saved(): void
    {
        $this->makeProduct('Existing', [], [['sku' => 'TAKEN']]);

        $check = $this->import($this->csv([
            ['handle' => 'good-one', 'name_en' => 'Good', 'price' => '10.00', 'sku' => 'G1'],
            ['handle' => 'bad-price', 'name_en' => 'Bad', 'price' => 'abc', 'sku' => 'B1'],
            ['handle' => 'dupe-sku', 'name_en' => 'Dupe', 'price' => '10.00', 'sku' => 'TAKEN'],
            ['handle' => 'no-cat', 'name_en' => 'No cat', 'price' => '10.00', 'categories' => 'nowhere'],
        ]));

        $check->assertSee('Row 3:')->assertSee('Row 4:')->assertSee('Row 5:')->assertSee('No category with the handle “nowhere”')
            ->assertSee('Another product already uses this SKU')->assertDontSee('Import now');
        $this->assertSame(1, Product::count()); // not even the good row
    }

    public function test_semicolon_files_and_missing_columns(): void
    {
        $this->import($this->csv([['handle' => 'semi', 'name_en' => 'Semicolon', 'price' => '5.00']], ';'));
        $this->assertTrue(Product::where('slug', 'semi')->exists());

        $bad = UploadedFile::fake()->createWithContent('x.csv', "title,cost\nA,1");
        $this->post('/admin/products/import/check', ['file' => $bad])->assertSessionHasErrors('file');
    }

    public function test_only_catalog_roles_may_import(): void
    {
        $this->actingAs(Admin::factory()->create(['role' => 'orders']), 'admin');
        $this->get('/admin/products/export')->assertForbidden();
        $this->get('/admin/products/import')->assertForbidden();
    }
}
