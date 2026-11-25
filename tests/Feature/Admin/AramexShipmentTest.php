<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Shipping\AramexCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Booking Aramex shipments, against faked replies.
 *
 * These prove our side: what Aramex is sent, what we store, and what happens when it refuses.
 * They cannot prove Aramex accepts the payload — that needs a real account.
 */
class AramexShipmentTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $this->admin = Admin::factory()->create();

        config([
            'services.aramex.username' => 'user@example.com',
            'services.aramex.password' => 'secret',
            'services.aramex.account_number' => '123456',
            'services.aramex.account_pin' => '1111',
            'services.aramex.account_entity' => 'DXB',
            'services.aramex.account_country' => 'AE',
        ]);
    }

    private function order(): Order
    {
        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->post('/cart/items', ['variant_id' => $variant, 'quantity' => 1])->assertRedirect();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_booking_a_shipment_stores_the_waybill_and_label(): void
    {
        Http::fake(['*CreateShipments' => Http::response([
            'HasErrors' => false,
            'Shipments' => [['ID' => '4471234567', 'ShipmentLabel' => ['LabelURL' => 'https://aramex.example/label.pdf']]],
        ])]);

        $order = $this->order();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 12.5, 'pieces' => 2, 'notify' => false])
            ->assertRedirect()->assertSessionHas('success');

        $fulfillment = $order->fresh()->fulfillments()->latest('id')->firstOrFail();
        $this->assertSame('4471234567', $fulfillment->tracking_number);
        $this->assertSame('https://aramex.example/label.pdf', $fulfillment->label_url);
        $this->assertSame('aramex', $fulfillment->carrier);
    }

    public function test_the_address_and_weight_sent_come_from_the_order(): void
    {
        Http::fake(['*CreateShipments' => Http::response(['HasErrors' => false, 'Shipments' => [['ID' => '999']]])]);

        $order = $this->order();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 9, 'notify' => false]);

        Http::assertSent(function ($request) use ($order) {
            $shipment = $request->data()['Shipments'][0];

            return $shipment['Reference1'] === $order->number
                && $shipment['Consignee']['Contact']['PersonName'] === 'Test Buyer'
                && $shipment['Consignee']['PartyAddress']['Line1'] === '12 Test Street'
                && $shipment['Details']['ActualWeight']['Value'] === 9.0;
        });
    }

    public function test_a_cash_order_asks_aramex_to_collect_the_money(): void
    {
        Http::fake(['*CreateShipments' => Http::response(['HasErrors' => false, 'Shipments' => [['ID' => '111']]])]);

        $order = $this->order();   // placed with cash on delivery

        $this->actingAs($this->admin, 'admin')->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 5, 'notify' => false]);

        Http::assertSent(function ($request) use ($order) {
            $details = $request->data()['Shipments'][0]['Details'];

            return $details['Services'] === 'CODS'
                && $details['CashOnDeliveryAmount']['Value'] === (float) $order->grand_total->toDecimal();
        });
    }

    public function test_an_error_from_aramex_is_shown_and_nothing_is_fulfilled(): void
    {
        // Aramex answers 200 with the problem inside the body.
        Http::fake(['*CreateShipments' => Http::response([
            'HasErrors' => true,
            'Notifications' => [['Code' => 'ERR01', 'Message' => 'Invalid account details']],
        ])]);

        $order = $this->order();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 5])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, $order->fresh()->fulfillments()->count(), 'Nothing is marked shipped when the booking failed.');
    }

    public function test_a_label_can_be_fetched_again_later(): void
    {
        Http::fake([
            '*CreateShipments' => Http::response(['HasErrors' => false, 'Shipments' => [['ID' => '4471234567']]]),
            '*PrintLabel' => Http::response(['HasErrors' => false, 'ShipmentLabel' => ['LabelURL' => 'https://aramex.example/again.pdf']]),
        ]);

        $order = $this->order();
        $this->actingAs($this->admin, 'admin')->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 5, 'notify' => false]);

        $fulfillment = $order->fresh()->fulfillments()->latest('id')->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get("/admin/fulfillments/{$fulfillment->id}/label")
            ->assertRedirect('https://aramex.example/again.pdf');

        $this->assertSame('https://aramex.example/again.pdf', $fulfillment->fresh()->label_url);
    }

    public function test_booking_needs_an_aramex_account(): void
    {
        config(['services.aramex.username' => null, 'services.aramex.password' => null]);

        $order = $this->order();

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 5])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_a_visitor_cannot_book_shipments(): void
    {
        $order = $this->order();

        $this->post("/admin/orders/{$order->id}/shipment", ['weight_kg' => 5])->assertRedirect();
        $this->assertGuest('admin');
    }
}
