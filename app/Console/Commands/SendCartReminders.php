<?php

namespace App\Console\Commands;

use App\Mail\CartReminder;
use App\Models\Cart;
use App\Models\EmailOptOut;
use App\Models\Order;
use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * One reminder for bags left behind, sent a few hours after the last change
 * (Admin → Settings → Notifications). Runs every 15 minutes.
 */
class SendCartReminders extends Command
{
    protected $signature = 'carts:send-reminders';

    protected $description = 'Email a reminder about bags left without checking out';

    public function handle(SettingsService $settings): int
    {
        if (! $settings->bool('emails.cart_reminder_enabled', true)) {
            return self::SUCCESS;
        }

        // The sequence: a nudge, a reminder the next day, a last word three days on. Each
        // step is optional — set its hours to 0 and it is skipped.
        $stages = [
            1 => max(0, (int) $settings->get('emails.cart_reminder_hours', 1)),
            2 => max(0, (int) $settings->get('emails.cart_reminder_hours_2', 24)),
            3 => max(0, (int) $settings->get('emails.cart_reminder_hours_3', 72)),
        ];

        $sent = 0;

        $previous = 0;

        foreach ($stages as $stage => $hours) {
            if ($hours <= 0) {
                continue;
            }

            $sent += $this->sendStage($stage, $hours, $previous);
            $previous = $hours;
        }

        $this->info($sent > 0 ? "{$sent} bag reminder(s) sent." : 'No bags to remind anyone about.');

        return self::SUCCESS;
    }

    /** One step of the sequence: bags that reached the previous step and are old enough. */
    private function sendStage(int $stage, int $hours, int $previousHours): int
    {
        $sent = 0;
        // Steps must also be spaced apart from each other, or a bag that is already days old
        // would receive the whole sequence in one run.
        $gap = max(1, $hours - $previousHours);

        Cart::query()
            ->where('reminder_stage', $stage - 1)
            ->where('updated_at', '<=', now()->subHours($hours))
            ->when($stage > 1, fn ($q) => $q->where('reminder_sent_at', '<=', now()->subHours($gap)))
            ->where('updated_at', '>=', now()->subDays(7))       // don't dig up old bags
            ->whereHas('items')
            ->with(['user', 'items.variant.product.media', 'items.variant.optionValues', 'items.variant.image'])
            ->each(function (Cart $cart) use (&$sent, $stage) {
                $email = $cart->user?->email ?? $cart->email;

                // Mark first, so a failure can never send twice — and write it straight to
                // the row, because touching updated_at would reset the abandonment clock and
                // the later steps would never come due.
                \Illuminate\Support\Facades\DB::table('carts')->where('id', $cart->id)->update([
                    'reminder_sent_at' => now(),
                    'reminder_stage' => $stage,
                ]);

                if (! $email || EmailOptOut::has($email) || ($cart->user && ! $cart->user->is_active)) {
                    return;
                }

                // They ordered since, perhaps on another device: no reminder.
                if (Order::query()->where('email', $email)->where('placed_at', '>=', $cart->updated_at)->exists()) {
                    return;
                }

                $lines = $cart->items->filter(fn ($i) => $i->variant?->isPurchasable())->map(fn ($i) => [
                    'name' => $i->variant->product->name,
                    'variant' => $i->variant->title() ?: null,
                    'quantity' => $i->quantity,
                    'price' => price_order($i->variant->price->multiply($i->quantity)),
                    'image' => ($i->variant->image ?? $i->variant->product->media->first())?->url('thumb'),
                ])->values()->all();

                if (! $lines) {
                    return;
                }

                $locale = $cart->user?->preferredLocale() ?? config('store.default_locale');
                $prefix = $locale === config('store.default_locale') ? '' : $locale.'.';

                Mail::to($email)->send(new CartReminder(
                    $cart, $lines, $stage,
                    \Illuminate\Support\Str::before($cart->user?->name ?? '', ' '),
                    URL::temporarySignedRoute($prefix.'cart.restore', now()->addDays(7), ['cart' => $cart->id]),
                    URL::signedRoute($prefix.'emails.unsubscribe', ['email' => $email]),
                    $locale,
                ));
                $sent++;
            });

        return $sent;
    }
}
