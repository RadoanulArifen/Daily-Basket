<?php

namespace App\Services\Orders;

use App\Actions\Checkout\PlaceOrder;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDraft;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Payments\CashOnDeliveryGateway;
use App\Services\Payments\ManualGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Draft orders made by staff (Admin → Orders → Create order): saving a draft to finish later,
 * discarding it, and placing it as a real order through the same PlaceOrder action as checkout.
 * The controller only validates input and redirects.
 */
class DraftOrderService
{
    public function __construct(
        private PlaceOrder $placeOrder,
        private CartService $carts,
        private OrderService $orders,
    ) {
    }

    /** Creates the draft, or updates the same draft when saved again (no trail of copies). */
    public function save(array $data, ?Admin $admin): OrderDraft
    {
        $lines = collect($data['lines'] ?? [])->filter();
        $variants = ProductVariant::query()->with('product:id,name')->whereIn('id', $lines->keys())->get();
        $subtotal = $variants->sum(fn ($variant) => $variant->price->minor * (int) ($lines[$variant->id] ?? 0));

        $attributes = [
            'admin_id' => $admin?->id,
            'customer_id' => $data['customer_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'items_count' => $lines->sum(),
            'subtotal' => $subtotal,
            'payload' => collect($data)->except('draft_id')->all(),
        ];

        // Saving again updates the same draft rather than leaving a trail of copies.
        return filled($data['draft_id'] ?? null)
            ? tap(OrderDraft::query()->findOrFail($data['draft_id']))->update($attributes)
            : OrderDraft::query()->create($attributes);
    }

    public function discard(OrderDraft $draft): void
    {
        $draft->delete();   // one draft, by its id
    }

    /** Places the draft as a real order (in the customer's language) and returns it. */
    public function place(array $data, Admin $admin): Order
    {
                $customer = isset($data['customer_id']) ? User::find($data['customer_id']) : User::query()->where('email', $data['email'])->first();
        $gateway = $data['payment'] === 'paid' ? app(ManualGateway::class) : app(CashOnDeliveryGateway::class);

        // A temporary bag with the chosen items, placed like any other order.
        $cart = Cart::create(['token' => Str::random(40), 'discount_code' => filled($data['discount_code'] ?? null) ? strtoupper($data['discount_code']) : null]);
        try {
            foreach ($data['lines'] as $variantId => $qty) {
                $cart->items()->create(['product_variant_id' => (int) $variantId, 'quantity' => (int) $qty]);
            }
            $this->carts->useCart($cart);

            // The order (and its emails) use the customer's language, not the admin's.
            $adminLocale = app()->getLocale();
            app()->setLocale($data['locale']);
            $order = $this->placeOrder->handle([
                'email' => $data['email'], 'phone' => $data['phone'], 'name' => $data['name'],
                'country' => $data['country'], 'state' => $data['state'] ?? null, 'city' => $data['city'], 'address_line' => $data['address_line'], 'building' => $data['building'] ?? null,
                'shipping_method' => $data['shipping_method'], 'payment_method' => $gateway->code(), 'notes' => $data['notes'] ?? null,
                'marketing_opt_in' => false, 'save_address' => false, 'locale' => $data['locale'],
            ], $gateway, $customer);
        } finally {
            app()->setLocale($adminLocale ?? app()->getLocale());
            // Remove only this temporary bag: WHERE id = ? AND token = ? (it never had an owner).
            DB::table('carts')->where('id', $cart->id)->where('token', $cart->token)->whereNull('user_id')->delete();
        }

        // The draft has become a real order, so it is no longer waiting to be finished.
        if ($id = $data['draft_id'] ?? null) {
            OrderDraft::query()->whereKey($id)->delete();
        }

        $this->orders->log($order, 'created_by_admin', $admin->name, $admin);
        if ($data['payment'] === 'paid') {
            $this->orders->markPaid($order, __('admin.orders.methods.manual'), $admin);
        }

        return $order;
    }
}
