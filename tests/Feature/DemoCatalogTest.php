<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_demo_seed_runs_and_admin_pages_render(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Product::count());
        $this->assertSame(8, Category::count());
        $this->assertSame(1, Product::where('status', 'draft')->count());

        $this->actingAs(\App\Models\Admin::firstOrFail(), 'admin');
        foreach (['/admin', '/admin/products', '/admin/categories', '/admin/media', '/admin/inventory', '/admin/inventory?stock=low', '/admin/inventory?stock=out'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (Product::pluck('id') as $id) {
            $this->get("/admin/products/{$id}/edit")->assertOk();
        }
    }
}
