<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $this->admin = Admin::factory()->create();
    }

    private function demo(bool $on = true): void
    {
        config(['store.demo' => $on, 'store.demo_email' => 'demo@example.com', 'store.demo_password' => 'Demo-login-2468!']);
    }

    public function test_a_demo_visitor_can_look_at_everything(): void
    {
        $this->demo();
        $this->actingAs($this->admin, 'admin');
        $product = Product::query()->firstOrFail();

        foreach (['/admin', '/admin/products', "/admin/products/{$product->id}/edit", '/admin/orders', '/admin/settings/general'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_nothing_a_demo_visitor_does_is_saved(): void
    {
        $this->demo();
        $this->actingAs($this->admin, 'admin');
        $product = Product::query()->firstOrFail();
        $was = $product->getTranslation('name', 'en');

        $this->put("/admin/products/{$product->id}", [
            'name' => ['en' => 'Changed by a visitor'],
            'slug' => $product->slug,
            'status' => $product->status->value,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame($was, $product->fresh()->getTranslation('name', 'en'));
    }

    public function test_deleting_is_blocked_too(): void
    {
        $this->demo();
        $this->actingAs($this->admin, 'admin');
        $product = Product::query()->firstOrFail();

        $this->delete("/admin/products/{$product->id}")->assertRedirect();

        $this->assertNotNull(Product::query()->find($product->id), 'The product is still there.');
    }

    public function test_signing_in_and_out_still_work(): void
    {
        $this->demo();
        $admin = Admin::factory()->create(['email' => 'demo@example.com', 'password' => bcrypt('Password123!')]);

        $this->post('/admin/login', ['email' => 'demo@example.com', 'password' => 'Password123!'])->assertRedirect();
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post('/admin/logout')->assertRedirect();
        $this->assertGuest('admin');
    }

    public function test_the_banner_says_so(): void
    {
        $this->actingAs($this->admin, 'admin');

        $this->demo(false);
        $this->get('/admin')->assertOk()->assertDontSee(__('admin.demo.banner'));

        $this->demo();
        $this->get('/admin')->assertOk()->assertSee(__('admin.demo.banner'));
    }

    public function test_a_real_shop_is_untouched_by_any_of_this(): void
    {
        $this->demo(false);
        $this->actingAs($this->admin, 'admin');
        $product = Product::query()->firstOrFail();

        $variant = $product->variants()->firstOrFail();

        $this->put("/admin/products/{$product->id}", [
            'name' => ['en' => 'Saved normally'],
            'slug' => $product->slug,
            'status' => $product->status->value,
            'variants' => [[
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price->toDecimal(),
                'stock_quantity' => $variant->stock_quantity,
                'is_active' => 1,
            ]],
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Saved normally', $product->fresh()->getTranslation('name', 'en'));
    }

    public function test_the_reset_command_refuses_to_run_on_a_real_shop(): void
    {
        $this->demo(false);

        $this->artisan('store:demo-reset --force')->assertFailed();
    }

    public function test_the_sign_in_page_hands_over_the_demo_details(): void
    {
        $this->demo(false);
        $this->get('/admin/login')->assertOk()->assertDontSee(__('admin.auth.demo_title'));

        $this->demo();
        $page = $this->get('/admin/login')->assertOk();
        $page->assertSee(__('admin.auth.demo_title'));
        $page->assertSee(config('store.demo_email'));
        $page->assertSee(config('store.demo_password'), false);
    }

    public function test_those_details_actually_sign_you_in(): void
    {
        $this->demo();
        Admin::factory()->create([
            'email' => config('store.demo_email'),
            'password' => bcrypt(config('store.demo_password')),
        ]);

        $this->post('/admin/login', [
            'email' => config('store.demo_email'),
            'password' => config('store.demo_password'),
        ])->assertRedirect();

        $this->assertAuthenticated('admin');
    }
}
