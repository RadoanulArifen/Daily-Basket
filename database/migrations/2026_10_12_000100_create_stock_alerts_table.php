<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Tell me when this is back" — one row per email per variant. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();          // for the "stop telling me" link
            $table->string('locale', 5)->default('en');     // answer in the language they asked in
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // One outstanding request per person per piece.
            $table->unique(['product_variant_id', 'email']);
            $table->index('notified_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
