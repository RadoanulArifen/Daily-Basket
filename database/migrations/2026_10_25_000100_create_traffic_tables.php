<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics → Traffic. Idempotent: safe to run on a database that already has the tables.
 * dateTime (not timestamp) for the visit times: strict MySQL (XAMPP) refuses a second
 * NOT NULL timestamp column with "Invalid default value".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('traffic_visits')) {
            Schema::create('traffic_visits', function (Blueprint $table) {
                $table->id();
                $table->char('visitor', 16);                 // HMAC of IP + browser; no IP is stored
                $table->char('country', 2)->nullable();
                $table->string('city', 100)->nullable();
                $table->decimal('lat', 7, 4)->nullable();      // city centre, for the live map pins
                $table->decimal('lon', 7, 4)->nullable();
                $table->boolean('is_new')->default(true);
                $table->unsignedInteger('pages')->default(1);
                $table->string('path', 255);                   // last page, without the query string
                $table->dateTime('started_at');
                $table->dateTime('last_seen_at');

                $table->index(['visitor', 'last_seen_at']);
                $table->index('started_at');
                $table->index('last_seen_at');
                $table->index(['country', 'started_at']);
            });
        }

        if (! Schema::hasTable('traffic_bot_counts')) {
            Schema::create('traffic_bot_counts', function (Blueprint $table) {
                $table->id();
                $table->date('day');
                $table->string('kind', 10);                    // search | ai | fake | other
                $table->unsignedInteger('hits')->default(0);
                $table->unique(['day', 'kind']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_bot_counts');
        Schema::dropIfExists('traffic_visits');
    }
};
