<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Product;
use App\Services\Analytics\Funnel;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
    }

    public function test_walking_through_the_shop_fills_the_funnel(): void
    {
        $product = Product::query()->where('slug', 'nord-accent')->firstOrFail();

        $this->get("/products/{$product->slug}")->assertOk();
        $this->post('/cart/items', ['variant_id' => $product->variants()->value('id'), 'quantity' => 1])->assertRedirect();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->get('/checkout')->assertOk();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $stages = collect(app(Funnel::class)->report()['stages'])->keyBy('stage');

        $this->assertSame(1, $stages['viewed']['count']);
        $this->assertSame(1, $stages['added']['count']);
        $this->assertSame(1, $stages['checkout']['count']);
        $this->assertSame(1, $stages['paid']['count']);
    }

    public function test_one_visitor_browsing_all_day_still_counts_once(): void
    {
        foreach (Product::query()->limit(4)->pluck('slug') as $slug) {
            $this->get("/products/{$slug}")->assertOk();
        }

        $stages = collect(app(Funnel::class)->report()['stages'])->keyBy('stage');

        $this->assertSame(1, $stages['viewed']['count'], 'Browsing four pieces is one shopper, not four.');
    }

    public function test_the_drop_between_steps_is_worked_out(): void
    {
        \Illuminate\Support\Facades\DB::table('funnel_counts')->insert([
            ['day' => now()->toDateString(), 'stage' => 'viewed', 'count' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['day' => now()->toDateString(), 'stage' => 'added', 'count' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['day' => now()->toDateString(), 'stage' => 'checkout', 'count' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['day' => now()->toDateString(), 'stage' => 'paid', 'count' => 15, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $stages = collect(app(Funnel::class)->report()['stages'])->keyBy('stage');

        $this->assertSame(40.0, $stages['added']['of_previous']);
        $this->assertSame(50.0, $stages['checkout']['of_previous']);
        $this->assertSame(75.0, $stages['paid']['of_previous']);
        $this->assertSame(15.0, $stages['paid']['of_top']);
    }

    public function test_a_search_with_no_results_is_remembered(): void
    {
        $this->get('/search?q=chesterfield+sofa')->assertOk();
        $this->get('/search?q=chesterfield+sofa')->assertOk();

        $miss = app(Funnel::class)->missedSearches()->firstWhere('term', 'chesterfield sofa');

        $this->assertNotNull($miss, 'The term should be recorded.');
        $this->assertSame(2, (int) $miss->count, 'Searching twice counts twice.');
    }

    public function test_a_search_that_finds_something_is_not_recorded(): void
    {
        $this->get('/search?q=sofa')->assertOk();

        $this->assertNull(app(Funnel::class)->missedSearches()->firstWhere('term', 'sofa'));
    }

    public function test_the_admin_sees_both(): void
    {
        $this->get('/search?q=chesterfield')->assertOk();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/admin/analytics')->assertOk()
            ->assertSee(__('admin.analytics.funnel_title'))
            ->assertSee(__('admin.analytics.misses_title'))
            ->assertSee('chesterfield');
    }
}
