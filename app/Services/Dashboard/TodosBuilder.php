<?php

namespace App\Services\Dashboard;

use App\Models\Admin;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Services\Settings\SettingsService;

/**
 * The dashboard's "Things to do": one count per kind of work waiting, shown only for the areas
 * this admin's role may open, each linking to the filtered list. (The setup guide is a different
 * list — SetupStepsBuilder — of one-off store set-up tasks.)
 */
class TodosBuilder
{
    public function __construct(private SettingsService $settings)
    {
    }

    /** @return list<array{count: int, key: string, icon: string, url: string}> */
    public function build(Admin $admin): array
    {
        $todos = [];
        if ($admin->mayAccess('orders')) {
            array_push($todos, ...$this->orderTodos());
        }
        if ($admin->mayAccess('reviews')) {
            $todos[] = $this->todo(ProductReview::query()->where('status', 'pending')->count(), 'reviews', 'star', route('admin.reviews.index'));
        }
        if ($admin->mayAccess('inventory')) {
            $todos[] = $this->todo($this->lowStockCount(), 'stock', 'triangle-alert', route('admin.inventory.index', ['stock' => 'low']));
        }

        return array_values(array_filter($todos, fn ($t) => $t['count'] > 0));
    }

    /** Orders to fulfil, card payments still pending, return requests waiting. */
    private function orderTodos(): array
    {
        $open = fn () => Order::query()->where('status', 'open');

        return [
            $this->todo($open()->where('fulfillment_status', 'unfulfilled')->where(fn ($q) => $q->where('payment_method', 'cod')->orWhere('payment_status', 'paid'))->count(),
                'fulfil', 'package-check', route('admin.orders.index', ['tab' => 'unfulfilled'])),
            $this->todo($open()->where('payment_method', '!=', 'cod')->where('payment_status', 'pending')->count(),
                'payments', 'credit-card', route('admin.orders.index', ['payment' => 'pending', 'method' => 'card'])),
            $this->todo(ReturnRequest::query()->where('status', 'requested')->count(),
                'returns', 'undo-2', route('admin.returns.index', ['status' => 'requested'])),
        ];
    }

    /** Active, tracked variants of active products at or below the low-stock level. */
    private function lowStockCount(): int
    {
        return ProductVariant::query()->where('is_active', true)->where('track_inventory', true)
            ->where('stock_quantity', '<=', $this->settings->lowStockThreshold())
            ->whereHas('product', fn ($p) => $p->where('status', 'active'))->count();
    }

    private function todo(int $count, string $key, string $icon, string $url): array
    {
        return ['count' => $count, 'key' => $key, 'icon' => $icon, 'url' => $url];
    }
}
