<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;

/**
 * Saved products. Guests keep them in the session; signed-in customers on their
 * account. A guest's list is added to their account when they sign in.
 * Registered as "scoped": one instance per request.
 */
class WishlistService
{
    public const SESSION_KEY = 'wishlist';

    public const MAX_ITEMS = 100;

    private ?array $ids = null;

    /** @return list<int> product ids, most recently saved first */
    public function ids(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        $user = auth('web')->user();

        return $this->ids = $user
            ? $user->wishlistItems()->pluck('product_id')->map(fn ($id) => (int) $id)->all()
            : array_values(array_map('intval', (array) session(self::SESSION_KEY, [])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** Save or un-save a product. Returns true if it is now saved. */
    public function toggle(Product $product): bool
    {
        $saved = ! $this->has($product->id);
        $user = auth('web')->user();

        if ($user) {
            $saved
                ? WishlistItem::query()->firstOrCreate(['user_id' => $user->id, 'product_id' => $product->id])
                : WishlistItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->delete();
        } else {
            $ids = array_values(array_diff($this->ids(), [$product->id]));
            if ($saved) {
                array_unshift($ids, $product->id);
            }
            session([self::SESSION_KEY => array_slice($ids, 0, self::MAX_ITEMS)]);
        }

        $this->ids = null;

        return $saved;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $visibleProducts  */
    public function products($visibleProducts): Collection
    {
        $ids = $this->ids();

        return $visibleProducts->whereIn('products.id', $ids)->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->values();
    }

    public function mergeGuestListInto(User $user): void
    {
        $ids = array_map('intval', (array) session()->pull(self::SESSION_KEY, []));
        $this->ids = null;

        if (! $ids) {
            return;
        }

        $existing = Product::query()->whereKey($ids)->pluck('id');
        WishlistItem::query()->insertOrIgnore($existing->map(fn ($id) => [
            'user_id' => $user->id,
            'product_id' => $id,
            'created_at' => now(),
        ])->all());
    }
}
