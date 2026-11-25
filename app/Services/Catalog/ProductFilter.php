<?php

namespace App\Services\Catalog;

use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admin product list's filters and sort order: search (names in both languages and SKUs),
 * status, category, featured, stock (in / low / out) and sort. Every value is bound by the
 * query builder; the allowed values are validated by the controller first.
 */
class ProductFilter
{
    use \App\Services\Concerns\AppliesFilters;

    /** @param array{q?: ?string, status?: ?string, category?: ?int, stock?: ?string, featured?: ?string, sort?: ?string} $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        $low = settings()->lowStockThreshold();

        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $this->search($q, $term))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['category'] ?? null, fn (Builder $q, int $id) => $q->whereHas('categories', fn ($c) => $c->whereKey($id)))
            ->when($filters['featured'] ?? null, fn (Builder $q) => $q->where('is_featured', true))
            ->when($filters['stock'] ?? null, fn (Builder $q, string $stock) => match ($stock) {
                'in' => $q->whereHas('variants', fn ($v) => $v->inStock()),
                'out' => $q->whereDoesntHave('variants', fn ($v) => $v->inStock()),
                'low' => $q->whereHas('variants', fn ($v) => $v->lowStock($low)),
            })
            ->tap(fn (Builder $q) => $this->sortBy($q, $filters['sort'] ?? null, [
                'newest' => fn ($q) => $q->latest('id'),
                'oldest' => fn ($q) => $q->oldest('id'),
                'name' => fn ($q) => $q->orderBy('name->en'),
                'name_desc' => fn ($q) => $q->orderByDesc('name->en'),
                'updated' => fn ($q) => $q->latest('updated_at'),
            ], 'newest'));
    }

    /** Names in both languages and variant SKUs, case-insensitively. */
    private function search(Builder $query, string $term): void
    {
        $query->where(fn (Builder $q) => $this->searchColumns($q, $term, ['name->en', 'name->ar'])
            ->orWhereHas('variants', fn ($v) => Search::like($v, 'sku', $term)));
    }
}
