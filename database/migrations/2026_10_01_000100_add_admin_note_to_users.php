<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private staff notes about a customer ("prefers WhatsApp", "VIP"). Never shown to them.
        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('admin_note'));
    }
};
