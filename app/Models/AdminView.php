<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A saved list view: a name and the filters it stands for, shown as a tab. */
class AdminView extends Model
{
    public const RESOURCES = ['orders', 'products', 'customers'];

    /** Query keys that are never part of a view. */
    public const IGNORED = ['page', '_token'];

    protected $fillable = ['resource', 'name', 'query', 'admin_id'];

    protected function casts(): array
    {
        return ['query' => 'array'];
    }

    /** Filters in a stable form, so "the current list" can be matched to a saved view. */
    public static function normalize(array $query): array
    {
        $query = array_filter(array_diff_key($query, array_flip(self::IGNORED)), fn ($v) => $v !== null && $v !== '' && $v !== []);
        ksort($query);

        return $query;
    }
}
