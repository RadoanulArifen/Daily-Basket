<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Requests for a bespoke piece, sent from the Custom Order page. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('user_id');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->index('product_id');
            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('dimensions', 160)->nullable();
            $table->string('material', 120)->nullable();
            $table->string('finish', 120)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('budget', 60)->nullable();
            $table->string('timeline', 60)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');   // new, in_progress, quoted, closed
            $table->text('admin_note')->nullable();
            $table->string('locale', 5)->default('en');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_requests');
    }
};
