<?php

namespace App\Mail;

use App\Models\Cart;
use App\Services\Notifications\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CartReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @param  list<array{name: string, variant: ?string, quantity: int, price: string, image: ?string}>  $lines */
    /**
     * @param  int  $stage  Which step of the sequence this is: 1 nudge, 2 reminder, 3 last word.
     */
    public function __construct(public Cart $cart, public array $lines, public int $stage, public string $name, public string $restoreUrl, public string $unsubscribeUrl, string $locale)
    {
        $this->locale($locale);
    }

    private function vars(): array
    {
        return ['{name}' => $this->name, '{store}' => (string) (settings()->trans('general.store_name', $this->locale) ?? config('app.name'))];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->wording('subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.carts.reminder', with: ['intro' => app(EmailTemplates::class)->customText('cart_reminder', 'intro', $this->locale, $this->vars())]);
    }

    /** This step's wording, or the plain reminder's if the shop has not written one. */
    private function wording(string $part): string
    {
        $templates = app(EmailTemplates::class);
        $event = $this->stage > 1 ? "cart_reminder_{$this->stage}" : 'cart_reminder';

        $text = $templates->customText($event, $part, $this->locale, $this->vars());

        return filled($text) ? $text : $templates->customText('cart_reminder', $part, $this->locale, $this->vars());
    }
}
