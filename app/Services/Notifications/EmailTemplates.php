<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Services\Settings\SettingsService;

/**
 * Customer email wording, editable in Admin → Settings → Notifications.
 * Empty fields fall back to the built-in text in lang/{en,ar}/store.php.
 *
 * Placeholders: {name} {number} {store} {carrier} {tracking}
 */
class EmailTemplates
{
    /** Emails about an order. */
    public const EVENTS = ['placed', 'shipped', 'delivered', 'cancelled', 'refunded'];

    /** The store's other customer emails, edited the same way. */
    public const CUSTOMER_EVENTS = ['welcome', 'enquiry', 'contact', 'invoice', 'payment', 'back_in_stock', 'cart_reminder_2', 'cart_reminder_3'];

    /** Every event that can be previewed in the admin (the bag reminder needs a live bag). */
    public const ALL_EVENTS = [...self::EVENTS, ...self::CUSTOMER_EVENTS];

    public const PLACEHOLDERS = ['{name}', '{number}', '{store}', '{carrier}', '{tracking}', '{amount}'];

    public function __construct(private SettingsService $settings) {}

    public function enabled(string $event): bool
    {
        return $event === 'placed' || $this->settings->bool("emails.{$event}_enabled", true);
    }

    public function subject(string $event, Order $order): string
    {
        return $this->fill($this->text($event, 'subject', $order->locale), $order);
    }

    public function intro(string $event, Order $order): string
    {
        return $this->fill($this->text($event, 'intro', $order->locale), $order);
    }

    /** Editable text for emails that aren't about an order, e.g. the bag reminder. */
    public function customText(string $event, string $part, string $locale, array $vars): string
    {
        return strtr($this->text($event, $part, $locale), $vars);
    }

    public function defaultText(string $event, string $part, string $locale): string
    {
        return __("store.email.templates.{$event}.{$part}", [], $locale);
    }

    private function text(string $event, string $part, string $locale): string
    {
        $custom = (array) $this->settings->get("emails.{$event}_{$part}", []);

        return filled($custom[$locale] ?? null) ? $custom[$locale] : $this->defaultText($event, $part, $locale);
    }

    private function fill(string $text, Order $order): string
    {
        return strtr($text, [
            '{name}' => \Illuminate\Support\Str::before($order->ship_name, ' '),
            '{number}' => (string) $order->number,
            '{store}' => (string) ($this->settings->trans('general.store_name', $order->locale) ?? config('app.name')),
            '{carrier}' => (string) ($order->carrier ?? ''),
            '{tracking}' => (string) ($order->tracking_number ?? ''),
            '{amount}' => ($refund = $order->refunds()->first()) ? price_order($refund->amount) : '',
        ]);
    }
}
