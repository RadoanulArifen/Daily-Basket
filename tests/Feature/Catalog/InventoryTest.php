<?php

namespace Tests\Feature\Catalog;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;

class InventoryTest extends CatalogTestCase
{
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->post('/admin/products', $this->productPayload());
        $this->variant = ProductVariant::firstOrFail();
    }

    public function test_inventory_page_lists_tracked_variants_with_filters(): void
    {
        $this->get('/admin/inventory')->assertOk()->assertSee('Woven Cushion')->assertSee('10 available');
        $this->get('/admin/inventory?stock=out')->assertOk()->assertDontSee('10 available');
        $this->get('/admin/inventory?q=sh-1')->assertOk()->assertSee('Woven Cushion');
    }

    public function test_stock_can_be_added_removed_and_set_with_history(): void
    {
        $url = "/admin/inventory/{$this->variant->id}/adjust";

        $this->post($url, ['mode' => 'add', 'quantity' => 5, 'reason' => 'received', 'note' => 'INV-22'])->assertSessionHas('success');
        $this->post($url, ['mode' => 'remove', 'quantity' => 3, 'reason' => 'damaged']);
        $this->post($url, ['mode' => 'set', 'quantity' => 20, 'reason' => 'correction']);

        $this->assertSame(20, $this->variant->fresh()->stock_quantity);
        $this->assertSame([10, 5, -3, 8], InventoryMovement::orderBy('id')->pluck('change')->all());
        $this->assertSame(20, InventoryMovement::latest('id')->value('quantity_after'));
    }

    public function test_cannot_remove_more_than_is_in_stock(): void
    {
        $this->post("/admin/inventory/{$this->variant->id}/adjust", ['mode' => 'remove', 'quantity' => 11, 'reason' => 'damaged'])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(10, $this->variant->fresh()->stock_quantity);
    }

    public function test_only_manual_reasons_are_accepted(): void
    {
        $this->post("/admin/inventory/{$this->variant->id}/adjust", ['mode' => 'add', 'quantity' => 1, 'reason' => 'initial'])
            ->assertSessionHasErrors('reason');
    }

    public function test_low_stock_threshold_can_be_changed(): void
    {
        $this->put('/admin/inventory/threshold', ['low_stock_threshold' => 12])->assertSessionHas('success');
        $this->get('/admin/inventory?stock=low')->assertSee('Woven Cushion');
    }
}
