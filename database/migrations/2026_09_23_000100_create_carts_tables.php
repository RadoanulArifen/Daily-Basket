<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A guest cart is found by the token in its cookie; a customer's by user_id.
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('updated_at'); // for clearing abandoned guest carts
        });

        // Only the variant and quantity are stored. Prices are always read from
        // the variant, so a cart can never hold a stale or tampered price.
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->index('product_variant_id');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->index('product_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
