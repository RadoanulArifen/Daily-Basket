<?php

namespace App\Actions\Checkout;

use App\Enums\InventoryReason;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Services\Discounts\DiscountService;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Currency\CurrencyService;
use App\Services\Inventory\InventoryService;
use App\Services\Orders\OrderService;
use App\Services\Orders\TotalsCalculator;
use App\Services\Payments\PaymentGateway;
use App\Services\Shipping\ShippingService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns the bag into an order. Everything is re-checked on the server inside one
 * transaction: product availability, prices, stock (rows locked), delivery price and totals.
 * Stock is taken now, so two customers can never buy the last item twice.
 */
class PlaceOrder
{
    public function __construct(
        private CartService $cart,
        private ShippingService $shipping,
        private TotalsCalculator $totals,
        private InventoryService $inventory,
        private OrderService $orders,
        private CurrencyService $currencies,
        private DiscountService $discounts,
    ) {}

    /** @param  array  $data  validated CheckoutRequest data */
    public function handle(array $data, PaymentGateway $gateway, ?User $user): Order
    {
        // Country + state (older callers may still send an emirate code).
        $data = \App\Support\AddressInput::normalize($data);
        $data['country'] = $data['country'] ?? \App\Support\Countries::default();
        $region = \App\Support\Countries::shippingRegion($data['country'], $data['state'] ?? null);
        $summary = $this->cart->summary();

        // Something changed since the checkout page was shown: send the customer back to review it.
        if ($summary->isEmpty() || $summary->notices) {
            throw ValidationException::withMessages(['cart' => $summary->notices ?: [__('store.checkout.cart_empty')]]);
        }

        $order = DB::transaction(function () use ($summary, $data, $gateway, $user, $region) {
            $base = config('store.base_currency');
            $variants = ProductVariant::query()
                ->whereKey(array_map(fn ($l) => $l->variant->id, $summary->lines))
                ->with(['product.media', 'image'])->lockForUpdate()->get()->keyBy('id');

            $subtotal = Money::zero($base);
            $weight = 0;
            $lines = [];

            foreach ($summary->lines as $line) {
                $variant = $variants->get($line->variant->id);
                $available = $variant?->availableQuantity();

                if (! $variant || ! $variant->isPurchasable() || ($available !== null && $line->item->quantity > $available)) {
                    throw ValidationException::withMessages(['cart' => __('store.checkout.stock_changed', ['name' => $line->product->name])]);
                }

                $lineTotal = $variant->price->multiply($line->item->quantity);
                $subtotal = $subtotal->add($lineTotal);
                $weight += ($variant->weight_grams ?? 0) * $line->item->quantity;
                $lines[] = [$line, $variant, $lineTotal];
            }

            // Automatic deals, on the fresh prices.
            $deals = app(\App\Services\Promotions\PromotionService::class)->apply(
                array_map(fn ($l) => ['key' => $l[1]->id, 'product_id' => $l[1]->product_id, 'quantity' => $l[0]->item->quantity, 'unit' => $l[1]->price], $lines),
                $base,
            );
            $afterDeals = $subtotal->subtract($deals['total']);

            // Discount code: row locked so its usage limit can't be overrun by two orders at once.
            $discount = null;
            $discountAmount = Money::zero($base);
            if ($code = $this->cart->current()?->discount_code) {
                $discount = Discount::query()->where('code', $code)->lockForUpdate()->first();

                if ($problem = $this->discounts->problem($discount, $afterDeals, $data['email'])) {
                    $this->cart->removeDiscount();
                    throw ValidationException::withMessages(['discount' => __('store.discount.removed', ['code' => $code, 'reason' => $problem])]);
                }

                $discountAmount = $this->discounts->itemsDiscount($discount, $afterDeals);
            }

            $rate = $this->shipping->rates($afterDeals->subtract($discountAmount), $region, $weight)[$data['shipping_method']] ?? null;
            if (! $rate) {
                throw ValidationException::withMessages(['shipping_method' => __('store.checkout.shipping_unavailable')]);
            }
            $shippingPrice = $this->discounts->givesFreeShipping($discount) ? Money::zero($base) : $rate->price;

            $totals = $this->totals->calculate($subtotal, $shippingPrice, Money::of($gateway->fee(), $base), $discountAmount->add($deals['total']));
            $display = $this->currencies->current();

            $order = Order::create([
                'access_token' => Str::random(48),
                'user_id' => $user?->id,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'locale' => app()->getLocale(),
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
                'ship_name' => $data['name'],
                'ship_phone' => $data['phone'] ?? null,
                'ship_country' => $data['country'],
                'ship_state' => $data['state'] ?? null,
                'ship_emirate' => \App\Support\Countries::emirate($data['country'], $data['state'] ?? null),
                'ship_city' => $data['city'],
                'ship_address_line' => $data['address_line'],
                'ship_building' => $data['building'] ?? null,
                'currency' => $base,
                'subtotal' => $totals->subtotal,
                'shipping_total' => $totals->shipping,
                'payment_fee' => $totals->fee,
                'discount_total' => $discountAmount,
                'discount_code' => $discount?->code,
                'promotion_total' => $deals['total'],
                'promotion_names' => $deals['applied'] ? array_map(fn ($a) => $a['name'] + ['amount' => $a['amount']->toDecimal()], $deals['applied']) : null,
                'tax_total' => $totals->tax,
                'grand_total' => $totals->grandTotal,
                'prices_include_tax' => $totals->pricesIncludeTax,
                'tax_rate' => $totals->taxRate,
                'tax_label' => $totals->taxLabel,
                'display_currency' => $display->code,
                'display_rate' => (string) $display->exchange_rate,
                'shipping_method' => $rate->code,
                'shipping_method_name' => $rate->name,
                'payment_method' => $gateway->code(),
                'customer_note' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            $order->forceFill(['number' => config('store.order_number_prefix').(1000 + $order->id)])->save();

            if ($discount) {
                $discount->increment('used_count');
                DiscountUsage::create(['discount_id' => $discount->id, 'order_id' => $order->id, 'email' => $order->email]);
            }

            foreach ($lines as [$line, $variant, $lineTotal]) {
                $image = $variant->image ?? $variant->product->media->first();

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->getTranslations('name'),
                    'variant_title' => array_filter(['en' => $line->variant->title('en'), 'ar' => $line->variant->title('ar')]) ?: null,
                    'options' => $line->item->options ?: null,
                    'sku' => $variant->sku,
                    'image' => $image?->url('thumb'),
                    'unit_price' => $variant->price,
                    'compare_at_price' => $variant->isOnSale() ? $variant->compare_at_price : null,
                    'quantity' => $line->item->quantity,
                    'line_total' => $lineTotal,
                ]);

                if ($variant->track_inventory) {
                    $this->inventory->adjust($variant, -$line->item->quantity, InventoryReason::Sold, $order->number, allowNegative: $variant->allow_backorder);
                }
            }

            if ($user) {
                $this->rememberAddress($user, $data);
            }

            $this->orders->log($order, 'placed');
            $this->cart->current()?->items()->delete();
            $this->cart->removeDiscount();

            app(\App\Services\Analytics\Funnel::class)->record('paid');

        return $order;
        });

        return $order->load('items');
    }

    /**
     * Address book: a delivery address the customer already has becomes their default;
     * a new one is saved (unless they unticked "save"), up to the per-customer limit.
     */
    private function rememberAddress(User $user, array $data): void
    {
        $existing = CustomerAddress::query()->where('user_id', $user->id)->get()->first(fn (CustomerAddress $a) => $a->matches($data));

        if ($existing) {
            $existing->update(['name' => $data['name'], 'phone' => $data['phone'] ?? $existing->phone]);
            $existing->makeDefault();

            return;
        }

        if (! ($data['save_address'] ?? true) || CustomerAddress::query()->where('user_id', $user->id)->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            return;
        }

        CustomerAddress::create([
            'user_id' => $user->id, 'name' => $data['name'], 'phone' => $data['phone'] ?? '',
            ...\App\Support\AddressInput::columns($data), 'address_line' => $data['address_line'],
            'building' => $data['building'] ?? null, 'is_default' => false,
        ])->makeDefault();
    }
}
