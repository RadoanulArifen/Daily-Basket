<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('origin')->nullable();          // "London, UK"
            $table->json('description')->nullable();     // short blurb for the brand page
            $table->string('logo')->nullable();          // path under public/ or storage
            $table->string('logo_small')->nullable();    // badge on product cards
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Small line above a banner title ("Furniture & Lighting").
        Schema::table('banners', function (Blueprint $table) {
            $table->json('eyebrow')->nullable()->after('title');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained()->nullOnDelete();
            $table->index('brand_id');
            $table->boolean('made_to_order')->default(false)->after('is_featured');
            $table->boolean('from_price')->default(false)->after('made_to_order');   // show "From $…"
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['made_to_order', 'from_price']);
        });
        Schema::table('banners', fn (Blueprint $t) => $t->dropColumn('eyebrow'));
        Schema::dropIfExists('brands');
    }
};
