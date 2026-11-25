<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Addresses become international: a country (ISO 3166-1 alpha-2) and a free-text state /
 * province / region next to the city. The old emirate column stays (nullable) for UAE orders —
 * Aramex prices and labels by emirate — and existing rows are filled in: a known emirate becomes
 * country AE with the emirate's name as the state; anything else gets the store's country.
 * Safe to run again.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['customer_addresses' => ['country', 'state', 'emirate'], 'orders' => ['ship_country', 'ship_state', 'ship_emirate']] as $table => [$country, $state, $emirate]) {
            Schema::table($table, function (Blueprint $t) use ($table, $country, $state, $emirate) {
                if (! Schema::hasColumn($table, $country)) {
                    $t->char($country, 2)->nullable();
                }
                if (! Schema::hasColumn($table, $state)) {
                    $t->string($state, 100)->nullable();
                }
                $t->string($emirate, 30)->nullable()->change();
            });

            $default = strtoupper((string) config('store.default_country', 'AE'));
            $emirates = (array) config('store.emirates');
            DB::table($table)->whereNull($country)->orderBy('id')->chunkById(500, function ($rows) use ($table, $country, $state, $emirate, $default, $emirates) {
                foreach ($rows as $row) {
                    $code = $row->{$emirate};
                    $uae = $code && in_array($code, $emirates, true) && $default === 'AE';
                    DB::table($table)->where('id', $row->id)->update([
                        $country => $uae ? 'AE' : $default,
                        $state => $uae ? __("store.emirates.{$code}", [], 'en') : null,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['customer_addresses' => ['country', 'state'], 'orders' => ['ship_country', 'ship_state']] as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
