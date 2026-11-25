<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A shipment: some or all of an order's items, with tracking.
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->unsignedSmallInteger('sequence');              // #1, #2 … within the order
            $table->string('carrier', 60)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamps();
        });

        Schema::create('fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->index('fulfillment_id');
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->index('order_item_id');
            $table->unsignedSmallInteger('quantity');
        });

        // Money given back: some items and/or delivery, optionally restocked.
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->index('order_id');
            $table->decimal('amount', 12, 2);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->string('gateway_reference')->nullable();       // Stripe refund id
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->index('admin_id');
            $table->timestamps();
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->index('refund_id');
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->index('order_item_id');
            $table->unsignedSmallInteger('quantity');
            $table->boolean('restocked')->default(false);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_total', 12, 2)->default(0)->after('grand_total');
        });

        // Orders already shipped become one shipment with all their items.
        $orders = DB::table('orders')->whereIn('fulfillment_status', ['shipped', 'delivered'])->get(['id', 'carrier', 'tracking_number', 'shipped_at', 'delivered_at', 'fulfillment_status']);
        foreach ($orders as $order) {
            $fulfillmentId = DB::table('fulfillments')->insertGetId([
                'order_id' => $order->id, 'sequence' => 1, 'carrier' => $order->carrier, 'tracking_number' => $order->tracking_number,
                'delivered_at' => $order->fulfillment_status === 'delivered' ? ($order->delivered_at ?? now()) : null,
                'created_at' => $order->shipped_at ?? now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('order_items')->where('order_id', $order->id)->get(['id', 'quantity']) as $item) {
                DB::table('fulfillment_items')->insert(['fulfillment_id' => $fulfillmentId, 'order_item_id' => $item->id, 'quantity' => $item->quantity]);
            }
        }

        // Orders already marked refunded were refunded in full.
        DB::table('orders')->where('payment_status', 'refunded')->update(['refunded_total' => DB::raw('grand_total')]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('refunded_total'));
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('fulfillment_items');
        Schema::dropIfExists('fulfillments');
    }
};
