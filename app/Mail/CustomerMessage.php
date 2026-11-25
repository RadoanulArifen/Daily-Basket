<?php

namespace App\Mail;

use App\Services\Notifications\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The store's plain customer emails — welcome, enquiry received, message received.
 *
 * Their wording comes from Admin → Settings → Notifications, so the shop owner can change
 * what they say without touching code.
 */
class CustomerMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $event  welcome | enquiry | contact
     * @param  array<string, ?string>  $vars  placeholder => value, e.g. ['{name}' => 'Layla']
     * @param  array<string, ?string>  $details  rows shown under the intro
     */
    public function __construct(
        public string $event,
        public array $vars = [],
        public array $details = [],
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public ?string $lang = null,   // not $locale: Mailable already has one
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->wording('subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.message', with: [
            'greeting' => $this->vars['{name}'] ?? null ? __('store.email.greeting', ['name' => $this->vars['{name}']], $this->chosenLocale()) : null,
            'intro' => $this->wording('intro'),
            'details' => $this->details,
            'actionUrl' => $this->actionUrl,
            'actionText' => $this->actionText,
            'footnote' => __('store.email.footnote', [], $this->chosenLocale()),
        ]);
    }

    private function wording(string $part): string   // not text(): Mailable defines one
    {
        return app(EmailTemplates::class)->customText($this->event, $part, $this->chosenLocale(), $this->vars + [
            '{store}' => (string) (settings()->trans('general.store_name', $this->chosenLocale()) ?? config('app.name')),
        ]);
    }

    private function chosenLocale(): string   // not locale(): Mailable defines one
    {
        return $this->lang ?: app()->getLocale();
    }
}
