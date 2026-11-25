<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every stock change, so you can always see why a number changed.
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('change');            // +10, -2 ...
            $table->integer('quantity_after');
            $table->string('reason', 30);         // see App\Enums\InventoryReason
            $table->string('note', 255)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_variant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
