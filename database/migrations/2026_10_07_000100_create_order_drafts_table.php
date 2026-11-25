<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Orders the team started in the admin but has not placed yet. They are kept apart from real
 * orders on purpose: a draft has no number, takes no stock and appears in no report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('customer_id');
            $table->string('name', 120)->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);   // minor units, for the list only
            $table->json('payload');
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_drafts');
    }
};
