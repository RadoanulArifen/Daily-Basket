<?php

// SECURITY: the cart token comes from an encrypted, signed cookie and is only ever used as a bound
// value in Cart::where('token', ?)->whereNull('user_id'); signed-in customers' carts are found by user_id.

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The shopping bag. Guests are recognised by a 30-day cookie; signed-in customers
 * by their account, so their bag follows them between devices.
 *
 * Carts only store variant + quantity. Prices and stock are always checked live.
 * Registered as "scoped": one instance per request.
 */
class CartService
{
    public const COOKIE = 'cart';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    public const MAX_LINE_QUANTITY = 99;

    public const MAX_LINES = 50;

    private ?Cart $cart = null;

    private bool $resolved = false;

    private ?int $count = null;

    /** Work with a given cart (used when an admin creates an order). */
    public function useCart(Cart $cart): void
    {
        $this->cart = $cart;
        $this->resolved = true;
        $this->count = null;
    }

    public function current(): ?Cart
    {
        if ($this->resolved) {
            return $this->cart;
        }

        $this->resolved = true;
        $user = auth('web')->user();

        if ($user) {
            return $this->cart = Cart::query()->where('user_id', $user->id)->first();
        }

        // The cart cookie is encrypted and signed (HMAC) by Laravel's EncryptCookies middleware,
        // so a visitor cannot forge or edit it; a tampered cookie reads as null. The token is
        // also a long random value and only matches guest carts (user_id is null).
        $token = request()->cookie(self::COOKIE);

        return $this->cart = is_string($token) && strlen($token) >= 32
            ? Cart::query()->where('token', $token)->whereNull('user_id')->first()
            : null;
    }

    public function getOrCreate(): Cart
    {
        if ($cart = $this->current()) {
            return $cart;
        }

        $user = auth('web')->user();
        $this->cart = Cart::create(['token' => Str::random(40), 'user_id' => $user?->id]);

        if (! $user) {
            Cookie::queue(Cookie::make(self::COOKIE, $this->cart->token, self::COOKIE_MINUTES, httpOnly: true, sameSite: 'lax'));
        }

        return $this->cart;
    }

    /** Total number of items, for the bag icon. */
    public function count(): int
    {
        return $this->count ??= (int) ($this->current()?->items()->sum('quantity') ?? 0);
    }

    /** @param  array<string, string>  $options  "Configure your piece" choices, e.g. ['Frame' => 'Oak'] */
    public function add(ProductVariant $variant, int $quantity, array $options = []): CartItem
    {
        app(\App\Services\Analytics\Funnel::class)->record('added');

        $options = CartItem::normaliseOptions($options);
        $hash = CartItem::hashOptions($options);
        $variant->loadMissing('product');

        if (! $variant->isPurchasable()) {
            throw ValidationException::withMessages(['variant_id' => __('store.cart.unavailable')]);
        }

        // Created outside the transaction: a failed add must not roll back a cart
        // this request has already handed out (and set a cookie for).
        $cart = $this->getOrCreate();

        return DB::transaction(function () use ($cart, $variant, $quantity, $options, $hash) {

            /** @var CartItem $item */
            $item = $cart->items()->where('product_variant_id', $variant->id)->where('options_hash', $hash)->lockForUpdate()->first()
                ?? new CartItem(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 0, 'options' => $options ?: null, 'options_hash' => $hash]);

            if (! $item->exists && $cart->items()->count() >= self::MAX_LINES) {
                throw ValidationException::withMessages(['variant_id' => __('store.cart.too_many_lines', ['max' => self::MAX_LINES])]);
            }

            // Stock is shared by every line of this variant (different configurations).
            $others = (int) $cart->items()->where('product_variant_id', $variant->id)->where('options_hash', '!=', $hash)->sum('quantity');
            $item->quantity = $this->allowedQuantity($variant, $item->quantity + $quantity, $item->quantity, $others);
            $item->save();
            $cart->touch();
            $this->count = null;

            return $item;
        });
    }

    public function update(CartItem $item, int $quantity): void
    {
        $this->assertOwned($item);

        if ($quantity <= 0) {
            $this->remove($item);

            return;
        }

        $variant = $item->variant()->with('product')->first();

        if (! $variant?->isPurchasable()) {
            $this->remove($item);

            throw ValidationException::withMessages(['quantity' => __('store.cart.unavailable')]);
        }

        $item->quantity = $this->allowedQuantity($variant, $quantity, 0);
        $item->save();
        $item->cart->touch();
        $this->count = null;
    }

    public function remove(CartItem $item): void
    {
        $this->assertOwned($item);
        $item->delete();
        $this->count = null;
    }

    /**
     * The cart with fresh prices. Items that can no longer be bought are removed and
     * quantities above the stock are lowered — each with a notice for the shopper.
     */
    public function summary(): CartSummary
    {
        $cart = $this->current();
        $currency = config('store.base_currency');

        if (! $cart) {
            return new CartSummary([], Money::zero($currency), 0);
        }

        $items = $cart->items()->with(['variant.product.media', 'variant.product.brandRecord', 'variant.optionValues', 'variant.image'])->get();
        $lines = [];
        $notices = [];
        $subtotal = Money::zero($currency);

        foreach ($items as $item) {
            $variant = $item->variant;
            $name = $variant?->product?->name;

            if (! $variant || ! $variant->isPurchasable()) {
                $item->delete();
                $notices[] = __('store.cart.notice_removed', ['name' => $name ?? __('store.cart.an_item')]);

                continue;
            }

            $available = $variant->availableQuantity();

            if ($available === 0) {
                $item->delete();
                $notices[] = __('store.cart.notice_sold_out', ['name' => $name]);

                continue;
            }

            if ($available !== null && $item->quantity > $available) {
                $item->update(['quantity' => $available]);
                $notices[] = __('store.cart.notice_reduced', ['name' => $name, 'count' => $available]);
            }

            $lineTotal = $variant->price->multiply($item->quantity);
            $subtotal = $subtotal->add($lineTotal);
            $lines[] = new CartLine($item, $variant, $variant->product, $variant->price, $lineTotal, $available);
        }

        $this->count = array_sum(array_map(fn (CartLine $l) => $l->item->quantity, $lines));

        // Automatic deals first ("Buy 2, get 1 free")…
        $deals = app(\App\Services\Promotions\PromotionService::class)->apply(
            array_map(fn (CartLine $l) => ['key' => $l->item->id, 'product_id' => $l->product->id, 'quantity' => $l->item->quantity, 'unit' => $l->unitPrice], $lines),
            $currency,
        );
        $afterDeals = $subtotal->subtract($deals['total']);

        // …then a discount code on what's left. A code that stopped working is removed with a notice.
        [$discount, $discountAmount] = [null, null];
        if ($cart->discount_code && $lines) {
            $service = app(\App\Services\Discounts\DiscountService::class);
            $discount = $service->find($cart->discount_code);

            if ($problem = $service->problem($discount, $afterDeals)) {
                $notices[] = __('store.discount.removed', ['code' => $cart->discount_code, 'reason' => $problem]);
                $cart->update(['discount_code' => null]);
                $discount = null;
            } else {
                $discountAmount = $service->itemsDiscount($discount, $afterDeals);
            }
        }

        return new CartSummary($lines, $subtotal, $this->count, $notices, $discount, $discountAmount,
            $deals['total']->isZero() ? null : $deals['total'],
            array_map(fn ($a) => ['name' => $a['name'], 'amount' => $a['amount']], $deals['applied']));
    }

    /**
     * When a guest signs in, their guest bag joins the one saved on their account.
     */
    public function mergeGuestCartInto(User $user): void
    {
        $token = request()->cookie(self::COOKIE);
        $guest = is_string($token) && $token !== '' ? Cart::query()->where('token', $token)->whereNull('user_id')->first() : null;

        $this->resolved = false;
        $this->cart = null;
        $this->count = null;

        if (! $guest) {
            return;
        }

        DB::transaction(function () use ($guest, $user) {
            $own = Cart::query()->where('user_id', $user->id)->first();

            if (! $own) {
                $guest->update(['user_id' => $user->id]);

                return;
            }

            foreach ($guest->items()->with('variant.product')->get() as $item) {
                $existing = $own->items()->where('product_variant_id', $item->product_variant_id)->where('options_hash', $item->options_hash)->first();
                $wanted = $item->quantity + ($existing?->quantity ?? 0);
                $available = $item->variant?->availableQuantity();
                $quantity = min($wanted, self::MAX_LINE_QUANTITY, $available ?? PHP_INT_MAX);

                if ($quantity > 0) {
                    $own->items()->updateOrCreate(['product_variant_id' => $item->product_variant_id, 'options_hash' => $item->options_hash], ['quantity' => $quantity, 'options' => $item->options]);
                }
            }

            $guest->delete();
            $own->touch();
        });

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public function applyDiscount(string $code): void
    {
        $service = app(\App\Services\Discounts\DiscountService::class);
        $discount = $service->find($code);
        $summary = $this->summary();

        if ($summary->isEmpty()) {
            throw ValidationException::withMessages(['code' => __('store.checkout.cart_empty')]);
        }

        if ($problem = $service->problem($discount, $summary->subtotal, auth('web')->user()?->email)) {
            throw ValidationException::withMessages(['code' => $problem]);
        }

        $this->getOrCreate()->update(['discount_code' => $discount->code]);
    }

    /** The email typed at checkout, so a reminder can be sent if the bag is left behind. */
    public function rememberEmail(string $email): void
    {
        if (($cart = $this->current()) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $cart->forceFill(['email' => mb_strtolower(trim($email))])->saveQuietly();
        }
    }

    public function removeDiscount(): void
    {
        $this->current()?->update(['discount_code' => null]);
    }

    /** Check a requested total against stock and the per-line limit. */
    private function allowedQuantity(ProductVariant $variant, int $wanted, int $alreadyInCart, int $inOtherLines = 0): int
    {
        $available = $variant->availableQuantity();
        if ($available !== null) {
            $available = max(0, $available - $inOtherLines);
        }

        if ($available !== null && $wanted > $available) {
            throw ValidationException::withMessages(['quantity' => $alreadyInCart >= $available
                ? __('store.cart.all_in_cart', ['count' => $available])
                : __('store.cart.only_available', ['count' => $available])]);
        }

        if ($wanted > self::MAX_LINE_QUANTITY) {
            throw ValidationException::withMessages(['quantity' => __('store.cart.max_quantity', ['max' => self::MAX_LINE_QUANTITY])]);
        }

        return $wanted;
    }

    private function assertOwned(CartItem $item): void
    {
        abort_unless($this->current() && $item->cart_id === $this->current()->id, 404);
    }
}
