<?php

namespace Tests\Feature\Growth;

use App\Models\Admin;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Query budgets for the busiest pages, measured on the full demo store. A page that
 * suddenly runs one query per product (an "N+1") fails here long before it's slow for customers.
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function queriesFor(string $url): int
    {
        $this->app->forgetScopedInstances();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_storefront_and_admin_pages_stay_within_query_budgets(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $this->get('/'); // warm settings/menu/currency caches, as on a live site

        $budgets = [
            '/' => 30,
            '/shop' => 25,
            '/collections/living' => 25,
            '/products/woven-wool-cushion' => 30,
            '/search?q=candle' => 25,
            '/sitemap.xml' => 10,
        ];

        foreach ($budgets as $url => $max) {
            $this->assertLessThanOrEqual($max, $used = $this->queriesFor($url), "{$url} used {$used} queries (budget {$max})");
        }

        $this->actingAs(Admin::firstOrFail(), 'admin');
        foreach (['/admin' => 30, '/admin/products' => 20, '/admin/orders' => 15, '/admin/customers' => 15, '/admin/analytics' => 20, '/admin/inventory' => 20] as $url => $max) {
            $this->assertLessThanOrEqual($max, $used = $this->queriesFor($url), "{$url} used {$used} queries (budget {$max})");
        }
    }
}
