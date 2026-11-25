<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\Notifications\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Shipped / delivered / cancelled emails, in the order's language. */
class OrderStatusUpdate extends Mailable implements ShouldQueue
{
    /** Sent by the queue worker; retried if Gmail is briefly unavailable. */
    public int $tries = 3;

    public int $backoff = 60;

    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $event)
    {
        $this->afterCommit();
        $this->locale($order->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: app(EmailTemplates::class)->subject($this->event, $this->order));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.status', with: [
            'order' => $this->order->loadMissing('items'),
            'intro' => app(EmailTemplates::class)->intro($this->event, $this->order),
            'event' => $this->event,
        ]);
    }
}
