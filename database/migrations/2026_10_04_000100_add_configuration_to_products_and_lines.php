<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * @note Idempotent on MySQL/MariaDB (which do not roll back DDL): every step checks
 *       Schema::hasColumn / Schema::hasIndex first. On SQLite the whole migration runs in a transaction.
 * step checks whether it already happened.
 */
return new class extends Migration
{
    private const OLD_UNIQUE = 'cart_items_cart_id_product_variant_id_unique';

    private const NEW_UNIQUE = 'cart_items_cart_id_product_variant_id_options_hash_unique';

    public function up(): void
    {
        // "Configure your piece": choices that describe how a piece is made (Frame: Oak…),
        // without changing its price. [{label, values: [...], swatches?: {value: "#hex"}}]
        if (! Schema::hasColumn('products', 'configuration')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('configuration')->nullable()->after('from_price');
            });
        }

        // The customer's choices travel with the bag line and the order line. The same
        // variant with different choices is a separate line.
        if (! Schema::hasColumn('cart_items', 'options')) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->json('options')->nullable()->after('quantity'));
        }
        if (! Schema::hasColumn('cart_items', 'options_hash')) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->string('options_hash', 40)->default('')->after('options'));
        }

        // New index first: MySQL's foreign key on cart_id needs an index starting with
        // cart_id at every moment, so the old one can only go once the new one exists.
        if (! Schema::hasIndex('cart_items', self::NEW_UNIQUE)) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->unique(['cart_id', 'product_variant_id', 'options_hash'], self::NEW_UNIQUE));
        }
        if (Schema::hasIndex('cart_items', self::OLD_UNIQUE)) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->dropUnique(self::OLD_UNIQUE));
        }

        if (! Schema::hasColumn('order_items', 'options')) {
            Schema::table('order_items', fn (Blueprint $t) => $t->json('options')->nullable()->after('variant_title'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'options')) {
            Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('options'));
        }
        if (! Schema::hasIndex('cart_items', self::OLD_UNIQUE)) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->unique(['cart_id', 'product_variant_id'], self::OLD_UNIQUE));
        }
        if (Schema::hasIndex('cart_items', self::NEW_UNIQUE)) {
            Schema::table('cart_items', fn (Blueprint $t) => $t->dropUnique(self::NEW_UNIQUE));
        }
        foreach (['options_hash', 'options'] as $column) {
            if (Schema::hasColumn('cart_items', $column)) {
                Schema::table('cart_items', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
        if (Schema::hasColumn('products', 'configuration')) {
            Schema::table('products', fn (Blueprint $t) => $t->dropColumn('configuration'));
        }
    }
};
