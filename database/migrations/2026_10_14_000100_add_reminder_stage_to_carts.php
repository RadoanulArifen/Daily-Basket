<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How far through the reminder sequence a bag has got.
 *
 * 0 = nothing sent, 1 = the nudge, 2 = the reminder, 3 = the last word. Existing bags that
 * already had a reminder are moved to stage 1 so nobody is nudged twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (! Schema::hasColumn('carts', 'reminder_stage')) {
                $table->unsignedTinyInteger('reminder_stage')->default(0)->after('reminder_sent_at');
                $table->index(['reminder_stage', 'updated_at']);
            }
        });

        \Illuminate\Support\Facades\DB::table('carts')->whereNotNull('reminder_sent_at')->update(['reminder_stage' => 1]);
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'reminder_stage')) {
                $table->dropIndex(['reminder_stage', 'updated_at']);
                $table->dropColumn('reminder_stage');
            }
        });
    }
};
