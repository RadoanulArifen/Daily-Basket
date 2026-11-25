<?php

namespace App\Mail;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the customer where their return stands, in the language they ordered in. */
class ReturnUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public ReturnRequest $return, public string $step)
    {
        $this->afterCommit();
        $this->locale($return->order->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("store.returns.email.{$this->step}_subject", ['number' => $this->return->number, 'order' => $this->return->order->number]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.returns.update', with: ['return' => $this->return, 'order' => $this->return->order, 'step' => $this->step]);
    }
}
