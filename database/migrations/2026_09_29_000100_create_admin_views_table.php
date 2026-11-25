<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saved list views ("Unpaid in Dubai"): a name + the filters, shown as a tab.
        Schema::create('admin_views', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 30);          // orders | products | customers
            $table->string('name', 40);
            $table->json('query');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamps();
            $table->index('resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_views');
    }
};
