<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** No admin route can ship without auth:admin and a role area (AdminRole::AREAS) or AdminRole::isOpenToAll(). */
class AdminRouteAreaMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_route_requires_the_admin_guard_and_has_an_area(): void
    {
        $open = ['admin.login', 'admin.login.store', 'admin.password.request', 'admin.password.email', 'admin.password.reset', 'admin.password.store', 'admin.password.update'];
        $unmapped = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'admin.') || in_array($name, $open, true)) {
                continue;
            }
            $this->assertContains('auth:admin', $route->gatherMiddleware(), "{$name} is not behind auth:admin");
            if (AdminRole::areaForRoute($name) === null && ! AdminRole::isOpenToAll($name)) {
                $unmapped[] = $name;
            }
        }
        $this->assertSame([], $unmapped, 'Admin routes without an area: '.implode(', ', $unmapped));
    }

    public function test_a_signed_in_customer_cannot_open_the_admin(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(), 'web')->get('/admin/orders')->assertRedirect(route('admin.login'));
    }

    public function test_another_customers_order_is_refused_even_with_its_link(): void
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $owner = User::factory()->create();
        $variant = \App\Models\ProductVariant::query()->firstOrFail();
        $this->actingAs($owner, 'web')->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($owner, 'web')->post('/checkout', [
            'email' => 'owner@example.com', 'name' => 'Owner', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Dubai', 'address_line' => '1 Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ]);
        $order = \App\Models\Order::query()->where('user_id', $owner->id)->latest('id')->firstOrFail();

        $this->actingAs(User::factory()->create(), 'web')->get('/orders/'.$order->access_token)->assertForbidden();
        $this->actingAs($owner, 'web')->get('/orders/'.$order->access_token)->assertOk();
    }
}
