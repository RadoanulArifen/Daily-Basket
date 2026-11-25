<?php

namespace App\Mail;

use App\Models\CustomOrderRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the store about a new request for a bespoke piece. */
class CustomOrderRequestReceived extends Mailable implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 60;

    use Queueable;

    public function __construct(public CustomOrderRequest $request)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('store.custom.email_subject', ['name' => $this->request->name]),
            replyTo: [new Address($this->request->email, $this->request->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.custom-order', with: ['request' => $this->request]);
    }
}
