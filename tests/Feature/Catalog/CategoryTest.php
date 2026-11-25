<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;

class CategoryTest extends CatalogTestCase
{
    public function test_categories_can_be_created_nested_and_listed(): void
    {
        $this->post('/admin/categories', ['name' => ['en' => 'Living', 'ar' => 'غرفة المعيشة'], 'is_active' => '1'])->assertSessionHasNoErrors();
        $living = Category::firstOrFail();
        $this->assertSame('living', $living->slug);

        $this->post('/admin/categories', ['name' => ['en' => 'Cushions'], 'parent_id' => $living->id, 'is_active' => '1'])->assertSessionHasNoErrors();

        $this->get('/admin/categories')->assertOk()->assertSeeInOrder(['Living', 'Cushions']);
        $this->get('/admin/categories/create')->assertOk();
        $this->get("/admin/categories/{$living->id}/edit")->assertOk();
    }

    public function test_a_category_cannot_be_moved_inside_itself(): void
    {
        $a = Category::create(['name' => ['en' => 'A'], 'slug' => 'a']);
        $b = Category::create(['name' => ['en' => 'B'], 'slug' => 'b', 'parent_id' => $a->id]);

        $this->put("/admin/categories/{$a->id}", ['name' => ['en' => 'A'], 'parent_id' => $b->id, 'is_active' => '1'])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_nesting_is_limited(): void
    {
        $a = Category::create(['name' => ['en' => 'A'], 'slug' => 'a']);
        $b = Category::create(['name' => ['en' => 'B'], 'slug' => 'b', 'parent_id' => $a->id]);
        $c = Category::create(['name' => ['en' => 'C'], 'slug' => 'c', 'parent_id' => $b->id]);

        $this->post('/admin/categories', ['name' => ['en' => 'D'], 'parent_id' => $c->id, 'is_active' => '1'])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_deleting_a_category_keeps_products_and_lifts_children(): void
    {
        $parent = Category::create(['name' => ['en' => 'Living'], 'slug' => 'living']);
        $child = Category::create(['name' => ['en' => 'Cushions'], 'slug' => 'cushions', 'parent_id' => $parent->id]);
        $this->post('/admin/products', $this->productPayload(['category_ids' => [$parent->id]]));

        $this->delete("/admin/categories/{$parent->id}")->assertRedirect('/admin/categories');

        $this->assertModelMissing($parent);
        $this->assertNull($child->fresh()->parent_id);
        $this->assertSame(1, Product::count());
        $this->assertSame(0, Product::first()->categories()->count());
    }
}
