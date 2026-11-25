<?php

namespace Tests\Feature\Admin;

use App\Actions\Catalog\SaveProduct;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ProductConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        config(['store.theme' => 'furnivo']);
        View::prependLocation(resource_path('themes/furnivo/views'));
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_typed_rows_become_the_choices_on_the_product_page(): void
    {
        $rows = [
            ['label' => 'Fabric', 'values' => 'Bouclé #efe9dc, Velvet #6b4423, Linen'],
            ['label' => 'Legs', 'values' => 'Oak, Walnut'],
        ];

        $this->assertSame([
            ['label' => 'Fabric', 'values' => ['Bouclé', 'Velvet', 'Linen'], 'swatches' => ['Bouclé' => '#efe9dc', 'Velvet' => '#6b4423']],
            ['label' => 'Legs', 'values' => ['Oak', 'Walnut']],
        ], SaveProduct::configuration($rows));
    }

    public function test_empty_rows_are_ignored(): void
    {
        $this->assertNull(SaveProduct::configuration([['label' => '', 'values' => ''], ['label' => 'Finish', 'values' => '']]));
    }

    public function test_the_editor_shows_what_the_product_already_has(): void
    {
        $product = Product::query()->whereNotNull('configuration')->firstOrFail();

        $this->get("/admin/products/{$product->id}/edit")->assertOk()
            ->assertSee(__('admin.products.fields.configuration'))
            // The rows are handed to the page as data and drawn by the browser.
            ->assertSee('Colour', false)
            ->assertSee('6b4423', false);   // the swatch colour, shown for editing
    }

    public function test_editing_the_choices_changes_the_product_page(): void
    {
        $product = Product::query()->whereNotNull('configuration')->firstOrFail();

        $this->put("/admin/products/{$product->id}", [
            'name' => ['en' => $product->getTranslation('name', 'en')],
            'slug' => $product->slug,
            'status' => $product->status->value,
            'configuration' => [['label' => 'Fabric', 'values' => 'Bouclé #efe9dc, Linen']],
            'variants' => [[
                'id' => $product->variants()->value('id'),
                'sku' => $product->variants()->value('sku'),
                'price' => '4200',
                'stock_quantity' => 5,
                'is_active' => 1,
            ]],
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Fabric', $product->fresh()->configuration[0]['label']);

        $this->get("/products/{$product->slug}")->assertOk()->assertSee('Fabric')->assertSee('Bouclé', false);
    }
}
