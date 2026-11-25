<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Where the courier's printable label lives, so nobody has to log into Aramex to reprint. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillments', function (Blueprint $table) {
            if (! Schema::hasColumn('fulfillments', 'label_url')) {
                $table->string('label_url', 500)->nullable()->after('tracking_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fulfillments', function (Blueprint $table) {
            if (Schema::hasColumn('fulfillments', 'label_url')) {
                $table->dropColumn('label_url');
            }
        });
    }
};
