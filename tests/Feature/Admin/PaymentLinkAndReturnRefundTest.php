<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Mail\CustomerMessage;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Services\Cart\CartService;
use App\Services\Returns\ReturnService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentLinkAndReturnRefundTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);
        $this->admin = Admin::factory()->create();
    }

    private function order(): Order
    {
        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->post('/cart/items', ['variant_id' => $variant, 'quantity' => 2])->assertRedirect();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_a_payment_link_can_be_emailed_to_the_customer(): void
    {
        $order = $this->order();
        Mail::fake();
        $this->actingAs($this->admin, 'admin');

        $this->post("/admin/orders/{$order->id}/email", ['what' => 'payment'])->assertRedirect();

        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'payment' && $mail->hasTo('buyer@example.com'));
    }

    public function test_only_the_three_kinds_of_email_are_accepted(): void
    {
        $order = $this->order();
        $this->actingAs($this->admin, 'admin');

        $this->post("/admin/orders/{$order->id}/email", ['what' => 'anything'])->assertSessionHasErrors('what');
    }

    private function returnFor(Order $order): ReturnRequest
    {
        app(SettingsService::class)->setMany('returns', ['enabled' => true, 'window_days' => 14]);
        $order->forceFill(['payment_status' => PaymentStatus::Paid, 'fulfillment_status' => \App\Enums\FulfillmentStatus::Delivered, 'delivered_at' => now()])->save();

        $item = $order->items->first();
        $this->post("/orders/{$order->access_token}/return", [
            'items' => [$item->id => 1], 'resolution' => 'refund', 'reason' => 'damaged',
        ])->assertRedirect();

        return ReturnRequest::query()->latest('id')->firstOrFail();
    }

    public function test_completing_a_return_can_pay_the_money_back(): void
    {
        $order = $this->order();
        $return = $this->returnFor($order);
        $returns = app(ReturnService::class);

        $returns->approve($return, null, $this->admin);
        $returns->receive($return->fresh(), true, null, $this->admin);

        $due = $returns->refundable($return->fresh());
        $this->assertGreaterThan(0, $due->minor, 'One of two chairs is worth refunding.');

        $returns->complete($return->fresh(), null, $this->admin, refund: true);

        $this->assertSame(1, $order->refunds()->count());
        $this->assertSame($due->minor, $order->refunds->sum(fn ($refund) => $refund->amount->minor));
        $this->assertSame('completed', $return->fresh()->status);
    }

    public function test_a_return_can_still_be_completed_without_refunding(): void
    {
        $order = $this->order();
        $return = $this->returnFor($order);
        $returns = app(ReturnService::class);

        $returns->approve($return, null, $this->admin);
        $returns->receive($return->fresh(), true, null, $this->admin);
        $returns->complete($return->fresh(), null, $this->admin);

        $this->assertSame(0, $order->refunds()->count(), 'Money only moves when asked.');
        $this->assertSame('completed', $return->fresh()->status);
    }
}
