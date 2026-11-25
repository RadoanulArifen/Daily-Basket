<?php

namespace App\Services\Notifications;

use App\Mail\OrderStatusUpdate;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderNotifier
{
    public function __construct(private EmailTemplates $templates) {}

    /** Email the customer about a status change. A mail failure never breaks the admin action. */
    public function send(Order $order, string $event): void
    {
        if (! $this->templates->enabled($event)) {
            return;
        }

        try {
            Mail::to($order->email)->send(new OrderStatusUpdate($order, $event));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
