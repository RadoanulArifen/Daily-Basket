<?php

namespace Tests\Feature\Furnivo;

use App\Enums\FulfillmentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Services\Cart\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Hash;

class FurnivoReturnsAndSmallPagesTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function deliveredOrder(): Order
    {
        app(SettingsService::class)->setMany('returns', ['enabled' => true, 'window_days' => 14]);

        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->post('/cart/items', ['variant_id' => $variant, 'quantity' => 2])->assertRedirect();
        $this->withCookie(CartService::COOKIE, Cart::query()->value('token'))->post('/checkout', [
            'email' => 'buyer@example.com', 'name' => 'Test Buyer', 'phone' => '+971501234567',
            'emirate' => 'dubai', 'city' => 'Jumeirah', 'address_line' => '12 Test Street',
            'shipping_method' => 'standard', 'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $order->forceFill(['fulfillment_status' => FulfillmentStatus::Delivered, 'delivered_at' => now()])->save();

        return $order;
    }

    public function test_a_customer_can_request_a_return(): void
    {
        $order = $this->deliveredOrder();

        $this->get("/orders/{$order->access_token}/return")->assertOk()
            ->assertSee(__('store.returns.heading'))
            ->assertSee('Nord Accent Chair')
            ->assertSee(__('store.returns.resolution'))
            ->assertSee(__('store.returns.reason'));

        $item = $order->items->first();
        $this->post("/orders/{$order->access_token}/return", [
            'items' => [$item->id => 1], 'resolution' => 'refund', 'reason' => 'damaged', 'note' => 'A leg arrived scratched.',
        ])->assertRedirect();

        $return = ReturnRequest::query()->sole();
        $this->assertSame('refund', $return->resolution);
        $this->assertSame('damaged', $return->reason);
    }

    public function test_a_return_needs_at_least_one_piece(): void
    {
        $order = $this->deliveredOrder();
        $item = $order->items->first();

        $this->from("/orders/{$order->access_token}/return")
            ->post("/orders/{$order->access_token}/return", ['items' => [$item->id => 0], 'resolution' => 'refund', 'reason' => 'damaged'])
            ->assertSessionHasErrors();

        $this->assertSame(0, ReturnRequest::query()->count());
    }

    public function test_the_store_password_page_uses_the_theme(): void
    {
        app(SettingsService::class)->setMany('preferences', [
            'password_enabled' => true,
            'password_hash' => Hash::make('letmein'),
            'password_message' => ['en' => 'Opening soon.'],
        ]);

        $this->get('/')->assertRedirect('/password');
        $this->get('/password')->assertOk()
            ->assertSee('Opening soon.')
            ->assertSee(__('store.password_page.enter'))
            ->assertSee(__('store.password_page.owner'));

        $this->post('/password', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/password', ['password' => 'letmein'])->assertRedirect();
        $this->get('/')->assertOk();
    }

    public function test_the_unsubscribe_page_confirms_it(): void
    {
        $url = \Illuminate\Support\Facades\URL::signedRoute('emails.unsubscribe', ['email' => 'one@example.com']);

        $this->get($url)->assertOk()
            ->assertSee(__('store.reminder.unsubscribed_title'))
            ->assertSee(__('store.cart.continue'));

        // They are on the do-not-email list from now on.
        $this->assertTrue(\App\Models\EmailOptOut::query()->where('email', 'one@example.com')->exists());
    }
}
