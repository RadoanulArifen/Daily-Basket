<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Order confirmation for the customer, in the language they ordered in. */
class OrderPlaced extends Mailable implements ShouldQueue
{
    /** Sent by the queue worker; retried if Gmail is briefly unavailable. */
    public int $tries = 3;

    public int $backoff = 60;

    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
        $this->locale($order->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: app(\App\Services\Notifications\EmailTemplates::class)->subject('placed', $this->order));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.placed', with: [
            'order' => $this->order->loadMissing('items'),
            'intro' => app(\App\Services\Notifications\EmailTemplates::class)->intro('placed', $this->order),
        ]);
    }
}
