<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * The pieces this visitor has looked at.
 *
 * Kept in the visitor's session rather than the database: it belongs to that person's visit,
 * it is worthless to anyone else, and it costs no query on a page that is not showing it.
 * Nothing identifying is stored — just product ids, newest first.
 */
class RecentlyViewed
{
    public const KEY = 'recently_viewed';

    /** Enough for a rail, not enough to bloat the cookie. */
    private const KEEP = 12;

    /** Remember that this product was opened. Newest first, no duplicates. */
    public function record(Product $product): void
    {
        $ids = collect($this->ids())
            ->reject(fn (int $id) => $id === $product->id)
            ->prepend($product->id)
            ->take(self::KEEP)
            ->values()
            ->all();

        Session::put(self::KEY, $ids);
    }

    /**
     * The products themselves, in the order they were seen, live ones only.
     *
     * @return Collection<int, Product>
     */
    public function products(?Product $except = null, int $limit = 6): Collection
    {
        $ids = collect($this->ids())->reject(fn (int $id) => $except && $id === $except->id)->take($limit);

        // Nothing looked at yet: fill the row from the catalogue, as the design does, rather
        // than leaving a gap on a first-time visitor's screen.
        if ($ids->isEmpty()) {
            return Product::query()
                ->where('status', 'active')
                ->when($except, fn ($q) => $q->whereKeyNot($except->id))
                ->with(['media', 'variants'])
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->with(['media', 'variants'])
            ->get()
            // Back into the order they were looked at, which the database does not know.
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();
    }

    /** @return array<int, int> */
    private function ids(): array
    {
        return collect(Session::get(self::KEY, []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(self::KEEP)
            ->values()
            ->all();
    }
}
