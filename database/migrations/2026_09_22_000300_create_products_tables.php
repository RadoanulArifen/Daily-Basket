<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft | active | archived
            $table->boolean('is_featured')->default(false);
            $table->string('brand', 100)->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        // e.g. "Size", "Colour" — up to three per product.
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->index('product_id');
            $table->json('name');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        // e.g. "Small", "Large".
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->index('product_option_id');
            $table->json('value');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        // Every product has at least one variant. A product without options has one
        // hidden "default" variant, so carts, orders and stock always point at a variant.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->nullable()->index(); // unique among live variants, checked in validation
            $table->string('barcode', 64)->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('allow_backorder')->default(false);
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->index('media_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes(); // kept so past orders can still point at them

            $table->index(['product_id', 'position']);
        });

        Schema::create('product_variant_option_value', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->index('product_option_value_id');
            $table->primary(['product_variant_id', 'product_option_value_id'], 'variant_value_primary');
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['category_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('media_product', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['product_id', 'media_id']);
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_product');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('product_variant_option_value');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('products');
    }
};
