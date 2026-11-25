<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What people did, counted by day.
 *
 * Deliberately counters rather than one row per event: a busy shop would write millions of
 * rows nobody reads, and the questions worth asking ("how many reached checkout last month")
 * are answered just as well by a daily tally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funnel_counts', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('stage', 20);            // viewed | added | checkout | paid
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['day', 'stage']);
            $table->index('day');
        });

        // What people searched for and found nothing — the shortest route to knowing what
        // the catalogue is missing, or what it calls things.
        Schema::create('search_misses', function (Blueprint $table) {
            $table->id();
            $table->string('term', 120);
            $table->unsignedInteger('count')->default(1);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            $table->unique('term');
            $table->index('count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_counts');
        Schema::dropIfExists('search_misses');
    }
};
