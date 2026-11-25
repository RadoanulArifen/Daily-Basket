<?php

namespace App\Services\Insights;

use App\Models\User;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admin customer list: sales figures per customer (orders, amount spent, last order) plus
 * search (name, email, phone), segment, country and sort. All values are bound by the query
 * builder; the allowed filter values are validated by the controller first.
 */
class CustomerQueryBuilder
{
    use \App\Services\Concerns\AppliesFilters;

    /** @param array{q?: ?string, segment?: ?string, country?: ?string, sort?: ?string} $filters */
    public function build(array $filters): Builder
    {
        $sales = fn ($q) => $q->countsAsSale();

        return User::query()
            ->withCount(['orders as sales_count' => $sales])
            ->withSum(['orders as spent' => $sales], 'grand_total')
            ->withMax(['orders as last_order_at' => $sales], 'placed_at')
            ->with(['addresses' => fn ($q) => $q->where('is_default', true)])
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $digits = ltrim(preg_replace('/\D/', '', $term), '0');
                $q->where(fn ($w) => $this->searchColumns($w, $term, ['name', 'email'])
                    ->when(strlen($digits) >= 5, fn ($p) => Search::like($p, 'phone', $digits, or: true)));
            })
            ->when($filters['segment'] ?? null, fn (Builder $q, string $segment) => match ($segment) {
                'returning' => $q->has('orders', '>=', 2),
                'new' => $q->where('created_at', '>=', now()->subDays(30)),
                'blocked' => $q->where('is_active', false),
                default => $q,
            })
            ->when($filters['country'] ?? null, fn (Builder $q, string $country) => $q->whereHas('addresses', fn ($a) => $a->where('country', $country)))
            ->tap(fn (Builder $q) => $this->sortBy($q, $filters['sort'] ?? null, [
                'newest' => fn ($q) => $q->latest('id'),
                'spent' => fn ($q) => $q->orderByDesc('spent'),
                'orders' => fn ($q) => $q->orderByDesc('sales_count'),
                'recent' => fn ($q) => $q->orderByDesc('last_order_at'),
            ], 'newest'));
    }
}
