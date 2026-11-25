<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Admin → Settings → Mail server → "Send test".
 *
 * Uses the same layout as every store email, so the owner also sees the logo and footer
 * the way customers will. Never queued: the result is shown on the page straight away.
 */
class TestEmail extends Mailable
{
    public function __construct(public string $sentBy = '') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('admin_mail.test.subject', ['store' => $this->store()]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.message', with: [
            'greeting' => null,
            'intro' => __('admin_mail.test.body', ['store' => $this->store()]),
            'details' => [
                __('admin_mail.test.sent_at') => now()->timezone(config('store.display_timezone'))->format('j M Y, H:i'),
                __('admin_mail.test.sent_by') => $this->sentBy,
                __('admin_mail.test.server') => trim(config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'), ':'),
            ],
            'actionUrl' => null,
            'actionText' => null,
            'footnote' => __('admin_mail.test.footnote'),
        ]);
    }

    private function store(): string
    {
        return (string) (settings()->trans('general.store_name') ?? config('app.name'));
    }
}
