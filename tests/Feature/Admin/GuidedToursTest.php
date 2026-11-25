<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Order;
use App\Services\Tours\AdminTourCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Guided tours in the admin: welcome screen, progress, roles, demo mode, and that every step points at something real. */
class GuidedToursTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
    }

    private function placeOrder(): Order
    {
        $variant = \App\Models\ProductVariant::query()->firstOrFail();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect();
        $token = \App\Models\Cart::query()->value('token');
        $this->withCookie(\App\Services\Cart\CartService::COOKIE, $token)->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'country' => 'AE', 'state' => 'Dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function owner(): Admin
    {
        $admin = Admin::factory()->create(['role' => AdminRole::Owner->value]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_the_welcome_screen_shows_once_and_can_be_put_off(): void
    {
        $this->shop();
        $admin = $this->owner();

        $this->get('/admin')->assertOk()->assertSee('Welcome to your store admin')->assertSee('Add your first product');

        $this->post('/admin/tours', ['action' => 'welcome_later'])->assertRedirect();
        $this->get('/admin')->assertDontSee('Welcome to your store admin');

        $this->post('/admin/tours', ['action' => 'welcome_never']);
        $this->flushSession();
        $this->actingAs($admin->fresh(), 'admin')->get('/admin')->assertDontSee('Welcome to your store admin');
        $this->assertTrue($admin->fresh()->tour_state['welcome']);
    }

    public function test_a_tour_runs_across_pages_and_finishes(): void
    {
        $this->shop();
        $admin = $this->owner();

        $this->post('/admin/tours', ['action' => 'start', 'tour' => 'product'])->assertRedirect();
        $this->assertSame('product', $admin->fresh()->tour_state['active']);

        // The browser gets the active tour's steps; step 2 points at the Add product button.
        $page = $this->get('/admin/products')->assertOk()->assertSee('id="admin-tour-data"', false);
        $this->assertStringContainsString('Every product starts here', $page->getContent());

        $this->postJson('/admin/tours', ['action' => 'go', 'tour' => 'product', 'step' => 3])->assertOk();
        $this->assertSame(3, $admin->fresh()->tour_state['progress']['product']);

        $this->postJson('/admin/tours', ['action' => 'finish', 'tour' => 'product'])
            ->assertOk()->assertJsonPath('toast', 'Tour finished: Add your first product')->assertJsonPath('next.key', 'payments');
        $state = $admin->fresh()->tour_state;
        $this->assertNull($state['active']);
        $this->assertContains('product', $state['done']);

        // Pausing keeps the progress, so "Continue" picks it up again.
        $this->post('/admin/tours', ['action' => 'start', 'tour' => 'storefront']);
        $this->postJson('/admin/tours', ['action' => 'go', 'tour' => 'storefront', 'step' => 2]);
        $this->postJson('/admin/tours', ['action' => 'pause'])->assertJsonPath('ok', true);
        $this->post('/admin/tours', ['action' => 'resume', 'tour' => 'storefront'])->assertRedirect(route('admin.online-store.customize'));
        $this->assertSame(2, $admin->fresh()->tour_state['progress']['storefront']);
    }

    public function test_tours_follow_the_admin_role(): void
    {
        $this->shop();
        $this->actingAs(Admin::factory()->create(['role' => AdminRole::Orders->value]), 'admin');

        $this->post('/admin/tours', ['action' => 'start', 'tour' => 'payments'])->assertForbidden();
        $this->get('/admin/orders')->assertOk()->assertSee('Handle a new order')->assertDontSee('Set up payments and delivery');
    }

    public function test_the_order_tour_waits_for_a_first_order(): void
    {
        $this->shop();
        $this->owner();

        $this->post('/admin/tours', ['action' => 'start', 'tour' => 'order'])->assertStatus(409);
        $this->placeOrder();
        $this->post('/admin/tours', ['action' => 'start', 'tour' => 'order'])->assertRedirect();
    }

    public function test_on_the_demo_each_visitor_has_their_own_progress(): void
    {
        $this->shop();
        config(['store.demo' => true]);
        $admin = $this->owner();

        $this->postJson('/admin/tours', ['action' => 'start', 'tour' => 'product'])->assertOk();
        $this->assertNull($admin->fresh()->tour_state);
        $this->assertSame('product', session('admin_tours')['active']);
    }

    public function test_every_step_points_at_something_on_its_page(): void
    {
        $this->shop();
        $order = $this->placeOrder();
        $this->owner();

        foreach (AdminTourCatalog::keys() as $tour) {
            foreach (AdminTourCatalog::steps($tour) as $i => $step) {
                $url = $step['url'] ?? route('admin.dashboard');
                $html = $this->get($url)->assertOk()->getContent();

                if (preg_match('/^a\[href="(.+)"\]$/', $step['target'], $m)) {
                    $this->assertStringContainsString('href="'.$m[1].'"', $html, "{$tour} step {$i}: no link to {$m[1]} on {$url}");
                } else {
                    $this->assertStringContainsString(substr($step['target'], 1, -1), $html, "{$tour} step {$i}: {$step['target']} missing on {$url}");
                }
                $this->assertNotSame("tours.admin.{$tour}", substr($step['title'], 0, strlen("tours.admin.{$tour}")), "{$tour} step {$i} has no text");
            }
        }
        $this->assertTrue($order->exists);
    }
}
