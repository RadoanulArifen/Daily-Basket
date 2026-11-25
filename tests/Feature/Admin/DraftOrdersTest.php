<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderDraft;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $this->actingAs(Admin::factory()->create(['name' => 'Store Owner']), 'admin');
    }

    private function variant(): ProductVariant
    {
        return Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->firstOrFail();
    }

    public function test_an_unfinished_order_can_be_saved_and_picked_up_again(): void
    {
        $variant = $this->variant();

        $this->post('/admin/orders/drafts', [
            'lines' => [$variant->id => 2], 'email' => 'draft@example.com', 'name' => 'Draft Customer',
            'city' => 'Jumeirah', 'address_line' => '12 Draft Street', 'emirate' => 'dubai',
        ])->assertRedirect();

        $draft = OrderDraft::query()->sole();
        $this->assertSame(2, $draft->items_count);
        $this->assertSame('Draft Customer', $draft->name);
        $this->assertSame('Store Owner', $draft->admin->name);

        // A draft is not an order: no number, no stock taken, nothing in the reports.
        $this->assertSame(0, Order::query()->count());

        $this->get('/admin/orders/drafts')->assertOk()->assertSee('Draft Customer');
        $this->get("/admin/orders/create?draft={$draft->id}")->assertOk()
            ->assertSee('draft@example.com')->assertSee('12 Draft Street');
    }

    public function test_saving_again_updates_the_same_draft(): void
    {
        $variant = $this->variant();

        $this->post('/admin/orders/drafts', ['lines' => [$variant->id => 1], 'name' => 'First go']);
        $draft = OrderDraft::query()->sole();

        $this->post('/admin/orders/drafts', ['draft_id' => $draft->id, 'lines' => [$variant->id => 3], 'name' => 'Second go']);

        $this->assertSame(1, OrderDraft::query()->count(), 'No trail of copies.');
        $this->assertSame('Second go', $draft->fresh()->name);
        $this->assertSame(3, $draft->fresh()->items_count);
    }

    public function test_placing_a_draft_makes_a_real_order_and_clears_the_draft(): void
    {
        $variant = $this->variant();
        $this->post('/admin/orders/drafts', ['lines' => [$variant->id => 1], 'email' => 'draft@example.com']);
        $draft = OrderDraft::query()->sole();

        $this->post('/admin/orders/create', [
            'draft_id' => $draft->id,
            'lines' => [$variant->id => 1],
            'email' => 'draft@example.com', 'phone' => '+971501234567', 'name' => 'Draft Customer',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Draft Street',
            'shipping_method' => 'standard', 'payment' => 'cod', 'locale' => 'en',
        ])->assertRedirect();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, OrderDraft::query()->count(), 'The draft is done with.');
    }

    public function test_a_draft_can_be_deleted(): void
    {
        $this->post('/admin/orders/drafts', ['lines' => [$this->variant()->id => 1], 'name' => 'Throwaway']);
        $draft = OrderDraft::query()->sole();

        $this->delete("/admin/orders/drafts/{$draft->id}")->assertRedirect();

        $this->assertSame(0, OrderDraft::query()->count());
    }

    public function test_drafts_are_for_admins_only(): void
    {
        auth('admin')->logout();

        $this->get('/admin/orders/drafts')->assertRedirect();
        $this->post('/admin/orders/drafts', ['lines' => []])->assertRedirect();
    }

    public function test_a_products_brand_is_chosen_from_the_brands_that_exist(): void
    {
        $product = Product::query()->where('slug', 'nord-accent')->firstOrFail();
        $brand = Brand::query()->where('name->en', '!=', $product->brand)->firstOrFail();

        $page = $this->get("/admin/products/{$product->id}/edit")->assertOk();
        $page->assertSee('name="brand_id"', false)->assertSee($brand->name);

        $this->put("/admin/products/{$product->id}", array_merge($this->productPayload($product), ['brand_id' => $brand->id]))
            ->assertSessionDoesntHaveErrors();

        $product->refresh();
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame($brand->name, $product->brand, 'The plain brand name is kept in step.');
    }

    private function productPayload(Product $product): array
    {
        return [
            'name' => ['en' => $product->getTranslation('name', 'en')],
            'slug' => $product->slug,
            'status' => $product->status->value,
            'variants' => [[
                'id' => $product->variants()->value('id'),
                'sku' => $product->variants()->value('sku'),
                'price' => '4200',
                'stock_quantity' => 5,
                'is_active' => 1,
            ]],
        ];
    }
}
