<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guided tours: each admin's progress (welcome seen, tour in progress and its step, finished
 * tours), so a tour can be continued on another computer. Safe to run again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admins', 'tour_state')) {
            Schema::table('admins', fn (Blueprint $table) => $table->json('tour_state')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admins', 'tour_state')) {
            Schema::table('admins', fn (Blueprint $table) => $table->dropColumn('tour_state'));
        }
    }
};
