<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Deleting a brand or a category without leaving orphans, the same way for both: inside one
 * transaction, lock the rows that point at it (SELECT … FOR UPDATE), re-point exactly those rows
 * with an explicit WHERE, check the count, then delete. If anything changed underneath, nothing
 * is deleted (409).
 */
class CatalogDeletion
{
    /** Products keep working; they simply no longer name a brand. */
    public function deleteBrand(Brand $brand): void
    {
        DB::transaction(function () use ($brand) {
            $this->repoint('products', 'brand_id', $brand->id, null);
            $brand->delete();
        });
    }

    /** Sub-categories move up one level; the category's own product links are removed. */
    public function deleteCategory(Category $category): void
    {
        DB::transaction(function () use ($category) {
            $this->repoint('categories', 'parent_id', $category->id, $category->parent_id);
            DB::table('category_product')->where('category_id', $category->id)->delete();
            $category->delete();
        });
    }

    /** UPDATE $table SET $column = $to WHERE $column = $from, on locked rows, count checked. */
    private function repoint(string $table, string $column, int $from, ?int $to): void
    {
        $ids = DB::table($table)->where($column, $from)->lockForUpdate()->pluck('id');
        $updated = DB::table($table)->where($column, $from)->whereIn('id', $ids)->update([$column => $to]);
        abort_if($updated !== $ids->count(), 409);
    }
}
