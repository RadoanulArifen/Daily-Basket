<?php

namespace Tests\Feature\Additions;

use App\Mail\CartReminder;
use App\Models\Cart;
use App\Models\EmailOptOut;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Checkout\CheckoutTestCase;

class CartRemindersTest extends CheckoutTestCase
{
    private function guestBagWithEmail(string $email = 'guest@example.com'): Cart
    {
        $this->addToBag($this->makeProduct('Throw '.uniqid(), [], [['price' => '120.00']])->variants()->first());
        $this->withCredentials()->cookieRequest()->postJson('/checkout/email', ['email' => $email])->assertOk();

        return Cart::where('token', $this->cartToken)->firstOrFail();
    }

    public function test_one_reminder_after_the_delay_for_guests_and_customers(): void
    {
        Mail::fake();
        $guest = $this->guestBagWithEmail();
        $this->assertSame('guest@example.com', $guest->email);

        $user = User::factory()->create(['email' => 'hessa@example.com']);
        $this->actingAs($user)->post('/cart/items', ['variant_id' => $this->makeProduct('Bowl')->variants()->first()->id]);
        auth('web')->logout();

        $this->artisan('carts:send-reminders');
        Mail::assertNothingQueued(); // too soon

        $this->travel(4)->hours();
        $this->artisan('carts:send-reminders');
        Mail::assertQueued(CartReminder::class, 2);
        Mail::assertQueued(CartReminder::class, fn ($m) => $m->hasTo('guest@example.com') && str_contains($m->restoreUrl, 'signature='));

        $this->artisan('carts:send-reminders');
        Mail::assertQueued(CartReminder::class, 2); // never twice
    }

    public function test_no_reminder_after_unsubscribing_or_ordering_elsewhere(): void
    {
        Mail::fake();
        $this->guestBagWithEmail('gone@example.com');
        EmailOptOut::create(['email' => 'gone@example.com']);

        $this->cartToken = null;
        $this->guestBagWithEmail('bought@example.com');
        // Same person ordered on another device after leaving this bag.
        $this->cartToken = null;
        $this->addToBag($this->makeProduct('Lamp')->variants()->first());
        $this->cookieRequest()->post('/checkout', $this->checkoutData(['email' => 'bought@example.com']));

        $this->travel(4)->hours();
        $this->artisan('carts:send-reminders');
        Mail::assertNotQueued(CartReminder::class); // (the order's own confirmation email is expected)
    }

    public function test_restore_and_unsubscribe_links_are_signed(): void
    {
        $cart = $this->guestBagWithEmail();

        $this->get('/cart/restore/'.$cart->id)->assertForbidden();
        $this->get(URL::temporarySignedRoute('cart.restore', now()->addDay(), ['cart' => $cart->id]))
            ->assertRedirect('/cart')->assertCookie(CartService::COOKIE, $cart->token);

        $this->get('/emails/unsubscribe?email=x@example.com')->assertForbidden();
        $this->get(URL::signedRoute('emails.unsubscribe', ['email' => 'X@Example.com']))->assertOk()->assertSee('x@example.com');
        $this->assertTrue(EmailOptOut::has('x@example.com'));
    }

    public function test_reminders_can_be_switched_off(): void
    {
        Mail::fake();
        settings()->setMany('emails', ['cart_reminder_enabled' => false]);
        app(\App\Services\Settings\SettingsService::class)->flush();
        $this->guestBagWithEmail();
        $this->travel(4)->hours();
        $this->artisan('carts:send-reminders');
        Mail::assertNothingQueued();
    }
}
