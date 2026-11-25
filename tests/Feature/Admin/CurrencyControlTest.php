<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Currency;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyControlTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $this->admin = Admin::factory()->create();
    }

    public function test_the_base_currency_cannot_be_switched_off(): void
    {
        $base = Currency::query()->where('is_base', true)->firstOrFail();

        // Even writing it directly, the model puts it back.
        $base->update(['is_active' => false]);
        $this->assertTrue($base->fresh()->is_active);

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/settings/currencies/{$base->id}/toggle")
            ->assertRedirect()->assertSessionHas('error');

        $this->assertTrue($base->fresh()->is_active);
    }

    public function test_other_currencies_can_be_switched_on_and_off(): void
    {
        $other = Currency::query()->where('is_base', false)->firstOrFail();
        $was = $other->is_active;

        $this->actingAs($this->admin, 'admin')->post("/admin/settings/currencies/{$other->id}/toggle")->assertRedirect();
        $this->assertSame(! $was, (bool) $other->fresh()->is_active);

        $this->actingAs($this->admin, 'admin')->post("/admin/settings/currencies/{$other->id}/toggle");
        $this->assertSame($was, (bool) $other->fresh()->is_active);
    }

    public function test_changing_the_base_converts_catalogue_prices(): void
    {
        $base = Currency::query()->where('is_base', true)->firstOrFail();
        $target = Currency::query()->where('is_base', false)->firstOrFail();
        $target->update(['exchange_rate' => 2]);        // 1 base = 2 target

        $variant = ProductVariant::query()->firstOrFail();
        $before = (int) $variant->getRawOriginal('price');

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/settings/currencies/{$target->id}/make-base")
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame($before * 2, (int) $variant->fresh()->getRawOriginal('price'), 'Prices are converted at the new base rate.');
        $this->assertTrue($target->fresh()->is_base);
        $this->assertFalse($base->fresh()->is_base);
        $this->assertEquals(1, (float) $target->fresh()->exchange_rate, 'The new base sits at 1.');
        $this->assertEquals(0.5, (float) $base->fresh()->exchange_rate, 'The old base is re-based against it.');
    }

    public function test_past_orders_are_left_alone_when_the_base_changes(): void
    {
        // A real order, placed the normal way.
        $variant = ProductVariant::query()->firstOrFail();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect();
        $this->withCookie(\App\Services\Cart\CartService::COOKIE, \App\Models\Cart::query()->value('token'))->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = \App\Models\Order::query()->latest('id')->firstOrFail();
        $before = (int) $order->getRawOriginal('grand_total');

        $target = Currency::query()->where('is_base', false)->firstOrFail();
        $target->update(['exchange_rate' => 3]);

        $this->actingAs($this->admin, 'admin')->post("/admin/settings/currencies/{$target->id}/make-base");

        $this->assertSame($before, (int) $order->fresh()->getRawOriginal('grand_total'), 'An order records what was charged; it is never rewritten.');
    }

    public function test_a_currency_without_a_rate_cannot_become_the_base(): void
    {
        $target = Currency::query()->where('is_base', false)->firstOrFail();
        $target->forceFill(['exchange_rate' => 0])->save();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/settings/currencies/{$target->id}/make-base")
            ->assertRedirect()->assertSessionHas('error');

        $this->assertFalse($target->fresh()->is_base);
    }
}
