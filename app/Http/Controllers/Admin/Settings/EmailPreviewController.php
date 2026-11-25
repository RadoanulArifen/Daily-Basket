<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Mail\CustomerMessage;
use App\Mail\OrderPlaced;
use App\Mail\OrderStatusUpdate;
use App\Models\Order;
use App\Services\Notifications\EmailTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Shows each customer email as the customer would see it, and can send a copy to the admin. */
class EmailPreviewController extends AdminController
{

    public function show(string $event): Response
    {
        abort_unless(auth('admin')->user()?->is_active, 403);   // admin only (also AdminController + route group)
        abort_unless(in_array($event, EmailTemplates::ALL_EVENTS, true), 404);

        return response($this->render($event))
            ->header('Content-Type', 'text/html')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function send(string $event): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->is_active, 403);   // admin only (also AdminController + route group)
        abort_unless(in_array($event, EmailTemplates::ALL_EVENTS, true), 404);

        $to = auth('admin')->user()?->email;
        if (blank($to)) {
            return back()->with('error', __('admin.settings.email.preview_no_address'));
        }

        try {
            Mail::to($to)->send($this->mailable($event));

            return back()->with('success', __('admin.settings.email.preview_sent', ['email' => $to]));
        } catch (Throwable $e) {
            return back()->with('error', __('admin.settings.mail_server.test_failed', ['error' => $e->getMessage()]));
        }
    }

    private function render(string $event): string
    {
        return $this->mailable($event)->render();
    }

    /** A real mailable, filled with a real order where there is one, otherwise a sample. */
    private function mailable(string $event)
    {
        if (in_array($event, EmailTemplates::CUSTOMER_EVENTS, true)) {
            $order = $this->sampleOrder();

            return new CustomerMessage(
                event: $event,
                // Every placeholder the wording can use, so a preview never shows {number}.
                vars: [
                    '{name}' => __('admin.settings.email.sample_name'),
                    '{number}' => $order->number,
                ],
                details: match ($event) {
                    'welcome' => [],
                    'invoice', 'payment' => [
                        __('store.order.number_short') => $order->number,
                        __('store.cart.total') => price_order($order->grand_total, $order),
                    ],
                    default => [__('store.contact.message') => __('admin.settings.email.sample_message')],
                },
                actionUrl: match ($event) {
                    'welcome' => store_route('account.dashboard'),
                    'invoice' => store_route('orders.invoice', ['token' => $order->access_token]),
                    'payment' => store_route('orders.show', ['token' => $order->access_token]),
                    default => null,
                },
                actionText: match ($event) {
                    'welcome' => __('store.account.title'),
                    'invoice' => __('store.order.invoice'),
                    'payment' => __('store.order.pay_link'),
                    default => null,
                },
            );
        }

        $order = $this->sampleOrder();

        return $event === 'placed' ? new OrderPlaced($order) : new OrderStatusUpdate($order, $event);
    }

    /** The most recent order, or one built in memory so the preview works on a new store. */
    private function sampleOrder(): Order
    {
        $order = Order::query()->with('items')->latest('id')->first();

        if ($order) {
            return $order;
        }

        // A store with no orders yet still needs something to look at, so this stands in
        // for one — with the totals filled so the email renders exactly as it would.
        $zero = \App\Support\Money::zero(config('store.base_currency'));
        $order = new Order([
            'number' => 'SH-1001',
            'email' => 'customer@example.com',
            'ship_name' => __('admin.settings.email.sample_name'),
            'ship_address_line' => '12 Example Street',
            'ship_city' => 'Jumeirah',
            'ship_emirate' => 'dubai',
            'ship_country' => 'AE',
            'ship_state' => 'Dubai',
            'ship_phone' => '+971 50 000 0000',
            'currency' => config('store.base_currency'),
            'locale' => app()->getLocale(),
            'subtotal' => $zero, 'shipping_total' => $zero, 'tax_total' => $zero,
            'discount_total' => $zero, 'payment_fee' => $zero, 'grand_total' => $zero,
            'shipping_method_name' => ['en' => __('store.checkout.delivery')],
            'payment_method' => 'cod',
        ]);
        $order->id = 0;
        $order->placed_at = now();
        abort_unless(auth('admin')->user()?->is_active, 403);   // admin-only, checked again here
        $order->access_token = \Illuminate\Support\Str::random(48);   // throw-away token for the example link; the order is never saved
        $order->setRelation('items', collect());

        return $order;
    }
}
