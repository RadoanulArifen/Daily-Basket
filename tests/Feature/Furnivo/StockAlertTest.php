<?php

namespace Tests\Feature\Furnivo;

use App\Mail\CustomerMessage;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Mail;

class StockAlertTest extends FurnivoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFurnivo();
    }

    private function soldOut(): ProductVariant
    {
        $variant = ProductVariant::query()->with('product')->firstOrFail();
        $variant->forceFill(['stock_quantity' => 0, 'track_inventory' => true, 'allow_backorder' => false])->save();

        return $variant->fresh();
    }

    public function test_the_form_appears_only_when_a_piece_is_sold_out(): void
    {
        $variant = ProductVariant::query()->with('product')->firstOrFail();
        $variant->forceFill(['stock_quantity' => 8, 'track_inventory' => true])->save();

        $this->get("/products/{$variant->product->slug}")->assertOk()->assertDontSee(__('store.stock_alert.submit'));

        $this->soldOut();

        $this->get("/products/{$variant->product->slug}")->assertOk()->assertSee(__('store.stock_alert.submit'));
    }

    public function test_a_visitor_can_ask_to_be_told(): void
    {
        $variant = $this->soldOut();

        $this->post('/stock-alerts', ['variant_id' => $variant->id, 'email' => 'Waiting@Example.com'])
            ->assertRedirect()->assertSessionHas('success');

        $alert = StockAlert::query()->firstOrFail();
        $this->assertSame('waiting@example.com', $alert->email, 'The address is stored lower-cased.');
        $this->assertNull($alert->notified_at);

        // Asking twice does not queue two emails.
        $this->post('/stock-alerts', ['variant_id' => $variant->id, 'email' => 'waiting@example.com']);
        $this->assertSame(1, StockAlert::query()->count());
    }

    public function test_asking_about_something_in_stock_is_refused(): void
    {
        $variant = ProductVariant::query()->firstOrFail();
        $variant->forceFill(['stock_quantity' => 3, 'track_inventory' => true])->save();

        $this->post('/stock-alerts', ['variant_id' => $variant->id, 'email' => 'someone@example.com'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, StockAlert::query()->count());
    }

    public function test_the_email_goes_out_once_the_piece_is_back(): void
    {
        $variant = $this->soldOut();
        $this->post('/stock-alerts', ['variant_id' => $variant->id, 'email' => 'waiting@example.com']);

        Mail::fake();

        // Still sold out: nobody is told.
        $this->artisan('store:stock-alerts')->assertSuccessful();
        Mail::assertNothingSent();

        $variant->forceFill(['stock_quantity' => 4])->save();

        $this->artisan('store:stock-alerts')->assertSuccessful();
        Mail::assertSent(CustomerMessage::class, fn ($mail) => $mail->event === 'back_in_stock' && $mail->hasTo('waiting@example.com'));

        $this->assertNotNull(StockAlert::query()->first()->notified_at);

        // And never twice.
        Mail::fake();
        $this->artisan('store:stock-alerts')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_the_stop_link_removes_the_request(): void
    {
        $variant = $this->soldOut();
        $this->post('/stock-alerts', ['variant_id' => $variant->id, 'email' => 'waiting@example.com']);
        $token = StockAlert::query()->value('token');

        $this->get("/stock-alerts/{$token}/cancel")->assertRedirect();

        $this->assertSame(0, StockAlert::query()->count());
    }
}
