<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every save of Online Store → Custom code, so any version can be restored.
        Schema::create('code_versions', function (Blueprint $table) {
            $table->id();
            $table->longText('css')->nullable();
            $table->longText('head')->nullable();
            $table->longText('body')->nullable();
            $table->json('domains')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_versions');
    }
};
