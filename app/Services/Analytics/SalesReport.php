<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Sales figures for a date range, grouped by UAE date. Orders are summed in PHP with
 * exact money maths, which is simple and fast enough for a single store's volumes.
 */
class SalesReport
{
    public const RANGES = [7, 30, 90, 365];

    private string $currency;

    private string $tz;

    public function __construct()
    {
        $this->currency = config('store.base_currency');
        $this->tz = config('store.display_timezone');
    }

    public function build(int $days): array
    {
        $end = CarbonImmutable::now($this->tz)->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $previousStart = $start->subDays($days);

        $orders = $this->orders($start, $end);
        $previous = $this->orders($previousStart, $start->subSecond());

        return [
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'totals' => $this->totals($orders),
            'previous' => $this->totals($previous),
            'series' => $this->series($orders, $start, $end, $days > 90 ? 'month' : 'day'),
            'topProducts' => $this->topProducts($orders),
            'countries' => $this->groupSum($orders, 'ship_country'),
            'payments' => $this->groupSum($orders, 'payment_method'),
            'discounts' => $this->groupSum($orders->whereNotNull('discount_code'), 'discount_code', 'discount_total'),
            'newCustomers' => User::query()->whereBetween('created_at', [$start->utc(), $end->utc()])->count(),
            'returningRate' => $this->returningRate($orders),
        ];
    }

    private function orders(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Order::query()
            ->countsAsSale()
            ->whereBetween('placed_at', [$from->utc(), $to->utc()])
            ->withSum('items as units', 'quantity')
            ->get(['id', 'email', 'placed_at', 'grand_total', 'discount_total', 'discount_code', 'ship_country', 'payment_method']);
    }

    private function totals(Collection $orders): array
    {
        $sales = $orders->reduce(fn (Money $sum, Order $o) => $sum->add($o->grand_total), Money::zero($this->currency));
        $count = $orders->count();

        return [
            'sales' => $sales,
            'orders' => $count,
            'average' => $count ? Money::ofMinor(intdiv($sales->minor + intdiv($count, 2), $count), $this->currency) : Money::zero($this->currency),
            'units' => (int) $orders->sum('units'),
        ];
    }

    /** @return list<array{label: string, sales: Money, orders: int}> one bucket per day or month */
    private function series(Collection $orders, CarbonImmutable $start, CarbonImmutable $end, string $unit): array
    {
        $key = fn ($date) => $unit === 'month' ? $date->format('Y-m') : $date->format('Y-m-d');
        $grouped = $orders->groupBy(fn (Order $o) => $key($o->placed_at->timezone($this->tz)));
        $buckets = [];

        foreach (CarbonPeriod::create($start, $unit === 'month' ? '1 month' : '1 day', $end) as $date) {
            $date = CarbonImmutable::parse($date, $this->tz);
            $group = $grouped->get($key($date), collect());
            $buckets[$key($date)] = [
                'label' => $date->translatedFormat($unit === 'month' ? 'M Y' : 'j M'),
                'sales' => $group->reduce(fn (Money $s, Order $o) => $s->add($o->grand_total), Money::zero($this->currency)),
                'orders' => $group->count(),
            ];
        }

        return array_values($buckets);
    }

    private function topProducts(Collection $orders, int $limit = 10): array
    {
        if ($orders->isEmpty()) {
            return [];
        }

        return OrderItem::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->get(['product_id', 'name', 'quantity', 'line_total'])
            ->groupBy(fn (OrderItem $i) => $i->product_id ?? 'deleted-'.$i->getTranslation('name', 'en'))
            ->map(fn (Collection $items) => [
                'product_id' => $items->first()->product_id,
                'name' => $items->first()->name,
                'units' => (int) $items->sum('quantity'),
                'sales' => $items->reduce(fn (Money $s, OrderItem $i) => $s->add($i->line_total), Money::zero($this->currency)),
            ])
            ->sortByDesc(fn ($row) => $row['sales']->minor)
            ->take($limit)
            ->values()
            ->all();
    }

    private function groupSum(Collection $orders, string $field, string $amountField = 'grand_total'): array
    {
        return $orders->groupBy($field)
            ->map(fn (Collection $group, $value) => [
                'key' => (string) $value,
                'orders' => $group->count(),
                'amount' => $group->reduce(fn (Money $s, Order $o) => $s->add($o->{$amountField}), Money::zero($this->currency)),
            ])
            ->sortByDesc('orders')
            ->values()
            ->all();
    }

    /** Share of this period's orders placed by customers who had ordered before. */
    private function returningRate(Collection $orders): ?int
    {
        if ($orders->isEmpty()) {
            return null;
        }

        // An order is "returning" if the same email has an earlier order (order ids only go up).
        $firstOrderId = Order::query()->countsAsSale()->whereIn('email', $orders->pluck('email')->unique())
            ->selectRaw('email, min(id) as first_id')->groupBy('email')->pluck('first_id', 'email');

        $returning = $orders->filter(fn (Order $o) => (int) ($firstOrderId[$o->email] ?? $o->id) < $o->id)->count();

        return (int) round($returning / $orders->count() * 100);
    }
}
