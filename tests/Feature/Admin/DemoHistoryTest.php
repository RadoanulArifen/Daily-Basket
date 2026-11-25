<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** store:demo-history fills months of orders, customers and analytics, and --fresh replaces them. */
class DemoHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_history_and_can_replace_it(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);

        $this->artisan('store:demo-history', ['--days' => 40])->assertSuccessful();
        $orders = Order::query()->count();
        $this->assertGreaterThan(40, $orders);
        $this->assertTrue(Order::query()->where('placed_at', '<', now()->subDays(30))->exists());
        $this->assertSame(180, User::query()->where('email', 'like', '%@example.net')->count());
        $this->assertSame(40 * 4, \DB::table('funnel_counts')->count());
        $this->assertNotNull(Order::query()->first()->number);

        $this->artisan('store:demo-history', ['--days' => 40, '--fresh' => true])->assertSuccessful();
        $this->assertSame($orders, Order::query()->count());   // same seed, replaced not doubled
    }

    public function test_it_refuses_a_live_shop(): void
    {
        $this->app['env'] = 'production';
        config(['store.demo' => false]);
        $this->artisan('store:demo-history')->assertFailed();
    }
}
