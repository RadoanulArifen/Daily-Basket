<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "You have a new order" for the store owner. */
class NewOrderForStore extends Mailable implements ShouldQueue
{
    /** Sent by the queue worker; retried if Gmail is briefly unavailable. */
    public int $tries = 3;

    public int $backoff = 60;

    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
        $this->locale(config('store.default_locale'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('admin.orders.email_subject', ['number' => $this->order->number, 'total' => $this->order->grand_total->toDecimal().' '.$this->order->currency]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.new-for-store', with: ['order' => $this->order->loadMissing('items')]);
    }
}
