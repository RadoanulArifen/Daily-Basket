<?php

namespace Tests\Feature\Growth;

use App\Mail\CartReminder;
use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** The three-step sequence: a nudge, a reminder the next day, a last word three days on. */
class CartReminderSequenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\SettingsSeeder::class, \Database\Seeders\CurrencySeeder::class, \Database\Seeders\FurnivoSeeder::class]);

        app(SettingsService::class)->setMany('emails', [
            'cart_reminder_enabled' => true,
            'cart_reminder_hours' => 1,
            'cart_reminder_hours_2' => 24,
            'cart_reminder_hours_3' => 72,
        ]);
    }

    private function abandonedBag(int $hoursAgo): Cart
    {
        $variant = Product::query()->where('slug', 'nord-accent')->firstOrFail()->variants()->value('id');
        $this->post('/cart/items', ['variant_id' => $variant, 'quantity' => 1])->assertRedirect();

        $cart = Cart::query()->latest('id')->firstOrFail();
        $cart->forceFill(['email' => 'shopper@example.com', 'updated_at' => now()->subHours($hoursAgo)])->saveQuietly();

        return $cart->fresh();
    }

    public function test_the_first_nudge_goes_out_after_an_hour(): void
    {
        $cart = $this->abandonedBag(hoursAgo: 2);
        Mail::fake();

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Mail::assertQueued(CartReminder::class, fn ($mail) => $mail->stage === 1);
        $this->assertSame(1, $cart->fresh()->reminder_stage);
    }

    public function test_a_fresh_bag_is_left_alone(): void
    {
        $this->abandonedBag(hoursAgo: 0);
        Mail::fake();

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_the_second_and_third_follow_in_their_turn(): void
    {
        $cart = $this->abandonedBag(hoursAgo: 30);
        Mail::fake();

        // First run: only the nudge, even though the bag is old enough for the second.
        $this->artisan('carts:send-reminders');
        Mail::assertQueued(CartReminder::class, 1);
        $this->assertSame(1, $cart->fresh()->reminder_stage);

        // A day later, the reminder follows — the steps are spaced, so both never go at once.
        $this->travel(24)->hours();
        Mail::fake();
        $this->artisan('carts:send-reminders');
        Mail::assertQueued(CartReminder::class, fn ($mail) => $mail->stage === 2);
        $this->assertSame(2, $cart->fresh()->reminder_stage);

        // And two days after that, the last word.
        $this->travel(48)->hours();
        Mail::fake();
        $this->artisan('carts:send-reminders');
        Mail::assertQueued(CartReminder::class, fn ($mail) => $mail->stage === 3);
        $this->assertSame(3, $cart->fresh()->reminder_stage);

        // Then it stops for good.
        Mail::fake();
        $this->artisan('carts:send-reminders');
        Mail::assertNothingQueued();
    }

    public function test_a_step_set_to_zero_is_skipped(): void
    {
        app(SettingsService::class)->setMany('emails', ['cart_reminder_hours_2' => 0]);
        $cart = $this->abandonedBag(hoursAgo: 100);

        Mail::fake();
        $this->artisan('carts:send-reminders');          // stage 1
        Mail::fake();
        $this->artisan('carts:send-reminders');          // stage 2 is off, so nothing

        Mail::assertNothingQueued();
        $this->assertSame(1, $cart->fresh()->reminder_stage);
    }

    public function test_the_whole_sequence_can_be_switched_off(): void
    {
        app(SettingsService::class)->setMany('emails', ['cart_reminder_enabled' => false]);
        $this->abandonedBag(hoursAgo: 5);

        Mail::fake();
        $this->artisan('carts:send-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
