<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing admins become owners, so nobody loses access.
        Schema::table('admins', function (Blueprint $table) {
            $table->string('role', 20)->default('owner')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Address book: a label ("Home", "Office") for each saved address.
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('label', 40)->nullable()->after('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_default']);
            $table->dropColumn('label');
        });
        Schema::table('admins', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
    }
};
