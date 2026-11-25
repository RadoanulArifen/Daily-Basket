<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abandoned-bag reminders: who to email, and whether we already did.
        Schema::table('carts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('discount_code');
            $table->timestamp('reminder_sent_at')->nullable()->after('email');
        });

        // People who asked not to receive reminder emails.
        Schema::create('email_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        // "Buy 2, get 1 free" style deals, applied automatically.
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable();   // optional: "Buy 2, get 1 free" is shown otherwise
            $table->unsignedSmallInteger('buy_quantity');
            $table->unsignedSmallInteger('get_quantity');
            $table->unsignedTinyInteger('get_percent')->default(100);   // 100 = free, 50 = half price
            $table->string('applies_to', 20)->default('all');           // all | category | products
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->index('category_id');
            $table->json('product_ids')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('promotion_total', 12, 2)->default(0)->after('discount_code');
            $table->json('promotion_names')->nullable()->after('promotion_total');
        });

        // Returns and exchanges.
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();   // R-1001
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->string('status', 20)->default('requested');   // requested | approved | rejected | received | completed
            $table->string('resolution', 20);                    // refund | exchange
            $table->string('reason', 30);
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('restocked')->default(false);
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->index('return_request_id');
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->index('order_item_id');
            $table->unsignedSmallInteger('quantity');
        });

        // Review photos are stored as media, kept out of the admin media library.
        Schema::table('media', function (Blueprint $table) {
            $table->string('collection', 20)->default('library')->after('disk');
            $table->index('collection');
        });

        Schema::create('media_product_review', function (Blueprint $table) {
            $table->foreignId('product_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->index('media_id');
            $table->unsignedTinyInteger('position')->default(0);
            $table->primary(['product_review_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_product_review');
        Schema::table('media', function (Blueprint $t) {
            $t->dropIndex(['collection']);
            $t->dropColumn('collection');
        });
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['promotion_total', 'promotion_names']));
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('email_opt_outs');
        Schema::table('carts', fn (Blueprint $t) => $t->dropColumn(['email', 'reminder_sent_at']));
    }
};
