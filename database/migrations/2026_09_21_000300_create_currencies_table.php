<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();          // ISO 4217, e.g. AED
            $table->json('name');                        // {"en": "UAE Dirham", "ar": "درهم إماراتي"}
            $table->string('symbol', 10);                // Shown in English, e.g. AED
            $table->string('symbol_ar', 10)->nullable(); // Shown in Arabic, e.g. د.إ
            $table->unsignedTinyInteger('decimal_places')->default(2);
            // How much 1 unit of the base currency (AED) is worth in this currency.
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
