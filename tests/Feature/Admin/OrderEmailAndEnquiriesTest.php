<?php

namespace Tests\Feature\Admin;

use App\Mail\CustomerMessage;
use App\Mail\OrderPlaced;
use App\Models\Admin;
use App\Models\CustomOrderRequest;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailAndEnquiriesTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);

        $variant = \App\Models\ProductVariant::query()->firstOrFail();
        $this->post('/cart/items', ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect();

        // The bag cookie is not carried between test requests, so pass it on.
        $token = \App\Models\Cart::query()->value('token');
        $this->withCookie(\App\Services\Cart\CartService::COOKIE, $token)->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_an_admin_can_email_the_order_confirmation_again(): void
    {
        $order = $this->order();
        Mail::fake();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->post("/admin/orders/{$order->id}/email", ['what' => 'confirmation'])->assertRedirect();

        // The order confirmation is queued, like the automatic one.
        Mail::assertQueued(OrderPlaced::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        $this->assertTrue(OrderEvent::query()->where('order_id', $order->id)->where('type', 'email_sent')->exists());
    }

    public function test_an_admin_can_email_the_invoice_link(): void
    {
        $order = $this->order();
        Mail::fake();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->post("/admin/orders/{$order->id}/email", ['what' => 'invoice'])->assertRedirect();

        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'invoice' && $mail->hasTo('buyer@example.com'));
    }

    public function test_only_the_two_kinds_of_email_are_accepted(): void
    {
        $order = $this->order();
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->post("/admin/orders/{$order->id}/email", ['what' => 'anything-else'])->assertSessionHasErrors('what');
    }

    public function test_enquiries_are_listed_by_status_and_can_be_worked_through(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $enquiry = CustomOrderRequest::query()->create([
            'name' => 'Layla Ahmed', 'email' => 'layla@example.com', 'phone' => '+971501234567',
            'dimensions' => '240 x 95 cm', 'material' => 'walnut', 'message' => 'A dining table, please.',
            'locale' => 'en',
        ]);

        $this->get('/admin/enquiries')->assertOk()->assertSee('Layla Ahmed')->assertSee('New');
        $this->get("/admin/enquiries/{$enquiry->id}")->assertOk()
            ->assertSee('240 x 95 cm')->assertSee('walnut')->assertSee('A dining table, please.');

        $this->put("/admin/enquiries/{$enquiry->id}", ['status' => 'quoted', 'admin_note' => 'Quoted 6,400.'])->assertRedirect();

        $this->assertSame('quoted', $enquiry->fresh()->status);
        $this->assertSame('Quoted 6,400.', $enquiry->fresh()->admin_note);

        // It has moved off the "new" tab.
        $this->get('/admin/enquiries?status=new')->assertOk()->assertDontSee('Layla Ahmed');
        $this->get('/admin/enquiries?status=quoted')->assertOk()->assertSee('Layla Ahmed');
    }

    public function test_enquiries_are_for_admins_only(): void
    {
        $this->get('/admin/enquiries')->assertRedirect();
    }
}
