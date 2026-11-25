<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who changed what in the admin, and when. Kept for a year (see AuditLog::prunable).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->string('action', 20);                 // created | updated | deleted | …
            $table->string('subject_type', 60);           // product, order, setting…
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();  // readable name at the time
            $table->json('changes')->nullable();          // {"price": ["120.00", "150.00"]}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex(['user_id', 'status']));
        Schema::dropIfExists('audit_logs');
    }
};
