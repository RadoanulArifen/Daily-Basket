<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessage extends Mailable implements ShouldQueue
{
    /** Sent by the queue worker; retried if Gmail is briefly unavailable. */
    public int $tries = 3;

    public int $backoff = 60;

    use Queueable;

    public function __construct(public array $data)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('store.contact.email_subject', ['name' => $this->data['name']]),
            replyTo: [new Address($this->data['email'], $this->data['name'])],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact', with: ['data' => $this->data]);
    }
}
