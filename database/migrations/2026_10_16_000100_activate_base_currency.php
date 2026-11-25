<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Put right a shop whose base currency was switched off.
 *
 * Prices are stored in the base currency, so an inactive base leaves the storefront with
 * nothing to show them in — and the admin had no way back, because the switcher only appears
 * for currencies that are not the base.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('currencies')->where('is_base', true)->update(['is_active' => true]);
    }

    public function down(): void
    {
        // Nothing to undo: an active base currency is the only valid state.
    }
};
