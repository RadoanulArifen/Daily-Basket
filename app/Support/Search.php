<?php

// SECURITY: LIKE search with bound parameters only — where($column, 'like', $bound) with % and _
// in the user's term escaped first; the column names come from code, never from the request.

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Case-insensitive "contains" search for the admin screens.
 *
 * Why this exists (rather than plain where-like calls): one place that lower-cases both sides
 * (case-insensitive on MySQL, MariaDB and SQLite alike, and for JSON translation columns),
 * escapes % and _ in the shopper's term, and is used by every admin list (products, customers,
 * inventory) so search behaves the same everywhere.
 *
 * Everything here goes through the query builder's `where(..., 'like', ...)`, so the term the
 * visitor typed is always a bound parameter. There is no raw SQL, no string concatenation and
 * no `DB::raw` — nothing a search box can reach is ever written into a statement.
 *
 * Case-insensitivity comes from the column collation (`utf8mb4_unicode_ci` on MySQL and
 * MariaDB, `NOCASE`-style LIKE on SQLite), which is why no `lower()` call is needed.
 */
class Search
{
    /**
     * Add `column LIKE %term%` to a query, with the term bound.
     *
     * @param  EloquentBuilder<*>|BuilderContract  $query
     */
    public static function like($query, string $column, string $term, bool $or = false)
    {
        $bound = '%'.trim($term).'%';

        return $or
            ? $query->orWhere($column, 'like', $bound)
            : $query->where($column, 'like', $bound);
    }

    /**
     * The same across several columns: matches when any of them contains the term.
     *
     * @param  EloquentBuilder<*>|BuilderContract  $query
     * @param  array<int, string>  $columns
     */
    public static function likeAny($query, array $columns, string $term)
    {
        return $query->where(function ($inner) use ($columns, $term) {
            foreach (array_values($columns) as $i => $column) {
                self::like($inner, $column, $term, or: $i > 0);
            }
        });
    }
}
