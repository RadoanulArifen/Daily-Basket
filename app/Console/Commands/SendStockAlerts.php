<?php

namespace App\Console\Commands;

use App\Mail\CustomerMessage;
use App\Models\StockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Tell people a piece is back.
 *
 * Runs on the scheduler rather than the moment stock changes, so a quick correction in the
 * admin (0 → 1 → 0 while counting) does not fire emails nobody wanted. Each alert is sent
 * once and then kept, so the same person is not told again on the next restock.
 */
class SendStockAlerts extends Command
{
    protected $signature = 'store:stock-alerts {--limit=200 : Most emails to send in one run}';

    protected $description = 'Email everyone waiting for a piece that is back in stock';

    public function handle(): int
    {
        $sent = 0;

        StockAlert::query()
            ->waiting()
            ->with(['variant.product'])
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (StockAlert $alert) use (&$sent) {
                $variant = $alert->variant;

                if (! $variant?->product || ! $variant->isPurchasable() || ! $variant->isInStock()) {
                    return;
                }

                Mail::to($alert->email)->send(new CustomerMessage(
                    event: 'back_in_stock',
                    vars: ['{product}' => $variant->product->getTranslation('name', $alert->locale, false)
                        ?: $variant->product->getTranslation('name', config('store.default_locale'), false)],
                    details: array_filter([
                        __('store.order.product') => $variant->product->name,
                        $variant->title() ? __('store.product.options') : null => $variant->title() ?: null,
                    ]),
                    actionUrl: route(($alert->locale === config('store.default_locale') ? '' : $alert->locale.'.').'products.show', [
                        'slug' => $variant->product->slug,
                    ]).'?variant='.$variant->id,
                    actionText: __('store.stock_alert.view_piece'),
                    lang: $alert->locale,
                ));

                $alert->forceFill(['notified_at' => now()])->save();
                $sent++;
            });

        $this->info($sent > 0 ? "{$sent} back-in-stock email(s) sent." : 'Nobody is waiting for anything that is back.');

        return self::SUCCESS;
    }
}
