<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // stored upper-case
            $table->string('type', 20);                    // percentage | fixed | free_shipping
            $table->decimal('value', 12, 2)->default(0);   // % or AED
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();            // all customers together
            $table->unsignedInteger('usage_limit_per_customer')->nullable(); // by email
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->string('email');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['discount_id', 'email']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->string('discount_code', 40)->nullable()->after('user_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code', 40)->nullable()->after('discount_total');
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->index('user_id');
            $table->string('name', 80);
            $table->unsignedTinyInteger('rating');   // 1–5
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->boolean('verified_purchase')->default(false);
            $table->string('locale', 5);
            $table->timestamps();
            $table->index(['product_id', 'status']);
            $table->unique(['product_id', 'user_id']); // one review per customer per product
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_average', 3, 2)->nullable()->after('brand');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_average');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body')->nullable();       // Markdown
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->boolean('show_contact_form')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20);        // hero | promo
            $table->json('title');
            $table->json('text')->nullable();
            $table->json('button_label')->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->index('image_id');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('pages');
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['rating_average', 'rating_count']));
        Schema::dropIfExists('product_reviews');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('discount_code'));
        Schema::table('carts', fn (Blueprint $t) => $t->dropColumn('discount_code'));
        Schema::dropIfExists('discount_usages');
        Schema::dropIfExists('discounts');
    }
};
