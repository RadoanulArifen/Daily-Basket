<?php

namespace Tests\Feature\Growth;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Tests\Feature\Checkout\CheckoutTestCase;

class AdminInsightsTest extends CheckoutTestCase
{
    private function placeOrder(string $price, int $qty = 1, array $data = []): Order
    {
        $this->cartToken = null;
        $this->addToBag($this->makeProduct('Item '.uniqid(), [], [['price' => $price]])->variants()->first(), $qty);
        $this->cookieRequest()->post('/checkout', $this->checkoutData($data))->assertSessionHasNoErrors();

        return Order::latest('id')->first();
    }

    public function test_customers_list_detail_and_blocking(): void
    {
        $user = User::factory()->create(['name' => 'Hessa Al Mansoori', 'email' => 'hessa@example.com', 'password' => 'secret-pass']);
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $this->makeProduct('Bowl', [], [['price' => '300.00']])->variants()->first()->id]);
        $this->actingAs($user)->post('/checkout', $this->checkoutData(['email' => $user->email]));
        auth('web')->logout();

        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->get('/admin/customers?sort=spent')->assertOk()->assertSee('Hessa Al Mansoori')->assertSee("AED\u{00A0}300.00");
        $this->get('/admin/customers?q=hessa')->assertSee('hessa@example.com');
        $this->get("/admin/customers/{$user->id}")->assertOk()->assertSee(Order::sole()->number)->assertSee('Al Barsha');

        $this->post("/admin/customers/{$user->id}/toggle")->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_active);
        auth('admin')->logout();
        $this->post('/login', ['email' => 'hessa@example.com', 'password' => 'secret-pass'])->assertSessionHasErrors();
    }

    public function test_analytics_counts_only_real_sales(): void
    {
        $this->placeOrder('100.00', 2);                                   // 200 → free delivery → 200
        $this->placeOrder('50.00', 1, ['emirate' => 'sharjah']);          // 50 + 25 = 75
        $cancelled = $this->placeOrder('999.00');
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->post("/admin/orders/{$cancelled->id}/cancel");

        $report = app(\App\Services\Analytics\SalesReport::class)->build(30);
        $this->assertSame('275.00', $report['totals']['sales']->toDecimal());
        $this->assertSame(2, $report['totals']['orders']);
        $this->assertSame('137.50', $report['totals']['average']->toDecimal());
        $this->assertSame(3, $report['totals']['units']);
        $this->assertSame(30, count($report['series']));
        $this->assertSame(['AE'], collect($report['countries'])->pluck('key')->sort()->values()->all());
        $this->assertSame(50, $report['returningRate']); // same email ordered before

        foreach ([7, 30, 90, 365] as $days) {
            $this->get('/admin/analytics?days='.$days)->assertOk();
        }
        $this->get('/admin/analytics')->assertSee("AED\u{00A0}275.00")->assertSee('Top products');
        $this->get('/admin/analytics?days=12')->assertSessionHasErrors('days');
        $this->get('/admin')->assertSee("AED\u{00A0}275.00");
    }

    public function test_admin_changes_are_audited_but_customer_actions_are_not(): void
    {
        $admin = Admin::factory()->create(['name' => 'Layla']);
        $this->actingAs($admin, 'admin');

        $this->post('/admin/categories', ['name' => ['en' => 'Lamps'], 'is_active' => '1']);
        $product = $this->makeProduct('Lamp', [], [['price' => '100.00']]);
        $variant = $product->variants()->first();
        $variant->update(['price' => '120.00']);
        $this->put('/admin/settings/shipping', ['flat_enabled' => '1', 'flat_name' => ['en' => 'Courier'], 'flat_rate' => '30.00', 'free_enabled' => '0']);

        $priceChange = AuditLog::where('subject_type', 'product_variant')->where('action', 'updated')->sole();
        // (SQLite hands back 100 where MySQL gives "100.00"; compare as numbers.)
        $this->assertEquals([100.0, 120.0], array_map('floatval', $priceChange->changes['price']));
        $this->assertSame($admin->id, $priceChange->admin_id);
        $this->assertTrue(AuditLog::where('subject_type', 'category')->where('subject_label', 'like', '%Lamps%')->exists());
        $this->assertTrue(AuditLog::where('subject_type', 'setting')->where('subject_label', 'shipping.flat_rate')->exists());

        // A customer's order changes stock and creates an order, but writes nothing here.
        auth('admin')->logout();
        $before = AuditLog::count();
        $this->placeOrder('50.00');
        $this->assertSame($before, AuditLog::count());

        $this->actingAs($admin, 'admin');
        $this->get('/admin/activity')->assertOk()->assertSee('Layla')->assertSee('Variant');
        $this->get('/admin/activity?type=setting')->assertSee('shipping.flat_rate');
        $this->assertFalse(AuditLog::query()->where('changes', 'like', '%password%')->exists());

        // Entries older than a year are pruned.
        AuditLog::query()->update(['created_at' => now()->subDays(400)]);
        $this->artisan('model:prune', ['--model' => [AuditLog::class]]);
        $this->assertSame(0, AuditLog::count());
    }
}
