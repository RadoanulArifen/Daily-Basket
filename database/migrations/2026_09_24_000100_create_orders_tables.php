<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->index('user_id');
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('emirate', 30);
            $table->string('city', 100);
            $table->string('address_line', 200);
            $table->string('building', 100)->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique();   // SH-1001, set right after insert
            $table->string('access_token', 64)->unique();          // for the guest order page
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->index('user_id');

            // Three separate statuses (see App\Enums).
            $table->string('status', 20)->default('open');
            $table->string('payment_status', 20)->default('pending');
            $table->string('fulfillment_status', 20)->default('unfulfilled');

            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('locale', 5);
            $table->boolean('marketing_opt_in')->default(false);

            // Shipping address, copied so later account edits don't change past orders.
            $table->string('ship_name', 120);
            $table->string('ship_phone', 20)->nullable();
            $table->string('ship_emirate', 30);
            $table->string('ship_city', 100);
            $table->string('ship_address_line', 200);
            $table->string('ship_building', 100)->nullable();
            $table->string('ship_country', 2)->default('AE');

            // Money: always in the base currency (AED), frozen at purchase.
            $table->string('currency', 3);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('payment_fee', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->boolean('prices_include_tax')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_label', 60)->nullable();

            // What the shopper was browsing in, for reference only.
            $table->string('display_currency', 3);
            $table->decimal('display_rate', 14, 6)->default(1);

            $table->string('shipping_method', 40);
            $table->json('shipping_method_name');
            $table->string('payment_method', 30);
            $table->string('carrier', 60)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('customer_note')->nullable();

            $table->timestamp('placed_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index(['payment_status', 'payment_method', 'placed_at']);
            $table->index('email');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->index('product_id');
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->index('product_variant_id');
            $table->json('name');            // {"en": ..., "ar": ...} at time of purchase
            $table->json('variant_title')->nullable();
            $table->string('sku', 64)->nullable();
            $table->string('image')->nullable(); // thumbnail URL at time of purchase
            $table->decimal('unit_price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->string('gateway', 30);
            $table->string('status', 20);   // pending | paid | failed | refunded | voided
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('reference')->nullable()->index(); // e.g. Stripe Checkout Session id
            $table->json('data')->nullable();
            $table->timestamps();
        });

        // Timeline shown on the admin order page.
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->string('type', 40);
            $table->text('message')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customer_addresses');
    }
};
