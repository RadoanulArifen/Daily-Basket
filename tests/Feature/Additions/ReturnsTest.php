<?php

namespace Tests\Feature\Additions;

use App\Mail\ReturnUpdate;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Checkout\CheckoutTestCase;

class ReturnsTest extends CheckoutTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addToBag($this->makeProduct('Throw', [], [['price' => '100.00', 'stock_quantity' => 5]])->variants()->first(), 2);
        $this->cookieRequest()->post('/checkout', $this->checkoutData());
        $this->order = Order::sole();
    }

    private function deliver(?\Carbon\Carbon $at = null): void
    {
        $this->order->forceFill(['fulfillment_status' => 'delivered', 'delivered_at' => $at ?? now()])->save();
    }

    private function url(string $suffix = ''): string
    {
        return '/orders/'.$this->order->access_token.'/return'.$suffix;
    }

    public function test_returns_open_only_after_delivery_and_within_the_window(): void
    {
        $this->get($this->url())->assertRedirect();                           // not delivered yet
        $this->get('/orders/'.$this->order->access_token)->assertDontSee('Request a return');

        $this->deliver(now()->subDays(20));
        $this->get($this->url())->assertRedirect();                           // window (14 days) has passed

        $this->deliver();
        $this->get('/orders/'.$this->order->access_token)->assertSee('Request a return or exchange');
        $this->get($this->url())->assertOk()->assertSee('Which items?');
    }

    public function test_full_return_flow_with_restock_and_emails(): void
    {
        Mail::fake();
        $this->deliver();
        $item = $this->order->items()->first();
        $variant = $item->variant;
        $this->assertSame(3, $variant->stock_quantity);

        $this->post($this->url(), ['items' => [$item->id => 1], 'resolution' => 'exchange', 'reason' => 'size_colour', 'note' => 'Charcoal please'])
            ->assertRedirect('/orders/'.$this->order->access_token);
        $return = ReturnRequest::sole();
        $this->assertSame('R-'.(1000 + $return->id), $return->number);
        Mail::assertQueued(ReturnUpdate::class, fn ($m) => $m->step === 'requested');

        // Only one item is left to return.
        $this->post($this->url(), ['items' => [$item->id => 2], 'resolution' => 'refund', 'reason' => 'other'])->assertSessionHasErrors('items');

        $this->actingAs(Admin::factory()->create(['role' => 'orders']), 'admin');
        $this->get('/admin/returns')->assertOk()->assertSee($return->number);
        $this->get("/admin/returns/{$return->id}")->assertOk()->assertSee('Charcoal please');

        $this->put("/admin/returns/{$return->id}", ['action' => 'receive'])->assertSessionHasErrors('status'); // must be approved first
        $this->put("/admin/returns/{$return->id}", ['action' => 'approve', 'note' => 'Courier comes Tuesday'])->assertSessionHas('success');
        $this->put("/admin/returns/{$return->id}", ['action' => 'receive', 'restock' => '1'])->assertSessionHas('success');
        $this->put("/admin/returns/{$return->id}", ['action' => 'complete'])->assertSessionHas('success');

        $this->assertSame('completed', $return->fresh()->status);
        $this->assertSame(4, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'change' => 1, 'reason' => 'returned', 'note' => $return->number]);
        $this->assertSame(['return_completed', 'return_received', 'return_approved', 'return_requested'], $this->order->events()->where('type', 'like', 'return_%')->pluck('type')->all());
        Mail::assertQueued(ReturnUpdate::class, fn ($m) => $m->step === 'approved');

        auth('admin')->logout();
        $this->get('/orders/'.$this->order->access_token)->assertSee($return->number)->assertSee('Completed');
    }

    public function test_rejected_items_can_be_requested_again_and_returns_can_be_switched_off(): void
    {
        $this->deliver();
        $item = $this->order->items()->first();
        $this->post($this->url(), ['items' => [$item->id => 2], 'resolution' => 'refund', 'reason' => 'changed_mind']);
        $this->actingAs(Admin::factory()->create(), 'admin')->put('/admin/returns/'.ReturnRequest::sole()->id, ['action' => 'reject', 'note' => 'Used item']);
        auth('admin')->logout();

        $this->get($this->url())->assertOk(); // rejected ones don't count

        settings()->setMany('returns', ['enabled' => false]);
        app(\App\Services\Settings\SettingsService::class)->flush();
        $this->get($this->url())->assertRedirect();
    }
}
