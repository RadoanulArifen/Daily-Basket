<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the admin searches: customers by name (email is already unique, so indexed) and
 * product variants by SKU (already indexed in the products migration — checked, not duplicated).
 * Safe to run again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->indexOnce('users', 'name');
        $this->indexOnce('product_variants', 'sku');
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_name_index')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_name_index'));
        }
    }

    private function indexOnce(string $table, string $column): void
    {
        $leading = collect(Schema::getIndexes($table))->map(fn ($i) => $i['columns'][0] ?? null)->all();
        if (! in_array($column, $leading, true)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($column));
        }
    }
};
