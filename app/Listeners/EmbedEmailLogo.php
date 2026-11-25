<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Puts the store logo inside every email (a cid: inline image) instead of linking to it.
 *
 * Many mail apps block outside pictures until the reader allows them, so a linked logo
 * shows as an empty box. The email layout still prints the normal image address, which
 * the admin email preview uses; here, just before sending, that address is swapped for
 * the embedded copy. No email template has to change.
 *
 * Laravel finds this listener by itself (event discovery in app/Listeners).
 */
class EmbedEmailLogo
{
    public function handle(MessageSending $event): void
    {
        try {
            $message = $event->message;
            if (! $message instanceof Email) {
                return;
            }

            $html = $message->getHtmlBody();
            $file = settings()->get('general.logo');
            if (! is_string($html) || blank($file)) {
                return;
            }

            $disk = Storage::disk(config('store.uploads.disk'));
            $url = $disk->url($file);
            $needles = array_unique(['src="'.e($url).'"', 'src="'.$url.'"']);
            $found = array_values(array_filter($needles, fn ($n) => str_contains($html, $n)));
            if ($found === [] || ! $disk->exists($file)) {
                return;
            }

            $data = $disk->get($file);
            if ($data === null || strlen($data) > 1024 * 1024) {
                return; // a 1 MB+ logo would make every email heavy; keep the link
            }

            $part = (new DataPart($data, 'logo.'.(pathinfo($file, PATHINFO_EXTENSION) ?: 'png'), $disk->mimeType($file) ?: 'image/png'))->asInline();
            $message->addPart($part);
            $message->html(str_replace($found, 'src="cid:'.$part->getContentId().'"', $html));
        } catch (\Throwable $e) {
            // The logo is a nicety: never stop an order email because of it.
            Log::warning('Email logo not embedded: '.$e->getMessage());
        }
    }
}
