<?php

namespace App\Services\Concerns;

use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;

/**
 * What every admin list query shares: a case-insensitive search over several columns (through
 * App\Support\Search, values always bound) and a named sort order with a default.
 * Used by ProductFilter and CustomerQueryBuilder.
 */
trait AppliesFilters
{
    /** WHERE (col1 LIKE ? OR col2 LIKE ? …) for the given columns. */
    protected function searchColumns(Builder $query, string $term, array $columns): Builder
    {
        return $query->where(function (Builder $q) use ($term, $columns) {
            foreach (array_values($columns) as $i => $column) {
                Search::like($q, $column, $term, or: $i > 0);
            }
        });
    }

    /**
     * Applies one of the named orders; unknown or empty names fall back to $default. Only the
     * orders listed in $orders exist (a whitelist written in code) — the request never names a column.
     *
     * @param  array<string, callable(Builder): mixed>  $orders
     */
    protected function sortBy(Builder $query, ?string $sort, array $orders, string $default): Builder
    {
        ($orders[$sort] ?? $orders[$default])($query);

        return $query;
    }
}
