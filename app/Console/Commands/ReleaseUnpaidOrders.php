<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Console\Command;

/**
 * Card orders hold their stock while the customer pays. If payment never
 * arrives, cancel the order and put the stock back. Runs every 10 minutes.
 */
class ReleaseUnpaidOrders extends Command
{
    protected $signature = 'orders:release-unpaid';

    protected $description = 'Cancel card orders left unpaid and return their stock';

    public function handle(OrderService $orders): int
    {
        $cutoff = now()->subMinutes(config('store.unpaid_order_minutes'));
        $count = 0;

        Order::query()
            ->where('payment_method', '!=', 'cod')
            ->where('payment_status', PaymentStatus::Pending)
            ->where('status', OrderStatus::Open)
            ->where('placed_at', '<', $cutoff)
            // Belt and braces: never touch an order that has money against it or has
            // already shipped, whatever its status column says. A card order that was
            // started and abandoned still has a pending payment row — that one is fine
            // to release; a captured one is not.
            ->whereDoesntHave('payments', fn ($p) => $p->whereIn('status', ['paid', 'succeeded', 'captured']))
            ->whereDoesntHave('fulfillments')
            ->each(function (Order $order) use ($orders, &$count) {
                $orders->cancel($order, __('admin.orders.auto_cancelled'), notify: false);
                $count++;
            });

        $this->info("Released {$count} unpaid order(s).");

        return self::SUCCESS;
    }
}
