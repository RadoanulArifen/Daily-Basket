<?php

namespace App\Services\Promotions;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Automatic deals ("Buy 2, get 1 free"). Registered as "scoped": one instance per request.
 *
 * How a deal is applied: the qualifying items in the bag are listed one unit at a time,
 * most expensive first, and split into groups of (buy + get). In each full group the
 * cheapest `get` units receive the discount — the usual "cheapest item free" rule.
 * An item can only benefit from one deal.
 */
class PromotionService
{
    private ?Collection $live = null;

    private array $categoryTree = [];

    public function live(): Collection
    {
        return $this->live ??= Promotion::query()->live()->get();
    }

    /** The first live deal a product qualifies for (for the badge on product pages). */
    public function forProduct(Product $product): ?Promotion
    {
        $categories = $this->categoriesOf([$product->id])[$product->id] ?? [];

        return $this->live()->first(fn (Promotion $p) => $this->covers($p, $product->id, $categories));
    }

    /**
     * @param  list<array{key: mixed, product_id: int, quantity: int, unit: Money}>  $lines
     * @return array{total: Money, applied: list<array{promotion: Promotion, name: array, amount: Money}>}
     */
    public function apply(array $lines, string $currency): array
    {
        $total = Money::zero($currency);
        $applied = [];

        if (! $lines || $this->live()->isEmpty()) {
            return ['total' => $total, 'applied' => []];
        }

        $categories = $this->categoriesOf(array_unique(array_column($lines, 'product_id')));
        $used = [];   // line key => units already given a deal

        foreach ($this->live() as $promotion) {
            // One entry per unit, most expensive first.
            $units = [];
            foreach ($lines as $line) {
                if (! $this->covers($promotion, $line['product_id'], $categories[$line['product_id']] ?? [])) {
                    continue;
                }
                $free = $line['quantity'] - ($used[$line['key']] ?? 0);
                for ($i = 0; $i < $free; $i++) {
                    $units[] = ['key' => $line['key'], 'unit' => $line['unit']];
                }
            }
            usort($units, fn ($a, $b) => $b['unit']->minor <=> $a['unit']->minor);

            $size = $promotion->buy_quantity + $promotion->get_quantity;
            $groups = intdiv(count($units), max(1, $size));
            if ($groups === 0) {
                continue;
            }

            $amount = Money::zero($currency);
            for ($g = 0; $g < $groups; $g++) {
                $group = array_slice($units, $g * $size, $size);
                foreach ($group as $u) {
                    $used[$u['key']] = ($used[$u['key']] ?? 0) + 1;   // the whole group is "spent" on this deal
                }
                foreach (array_slice($group, -$promotion->get_quantity) as $u) {
                    $amount = $amount->add($u['unit']->percentage((string) $promotion->get_percent));
                }
            }

            if (! $amount->isZero()) {
                $total = $total->add($amount);
                $applied[] = ['promotion' => $promotion, 'name' => ['en' => $promotion->label('en'), 'ar' => $promotion->label('ar')], 'amount' => $amount];
            }
        }

        return ['total' => $total, 'applied' => $applied];
    }

    private function covers(Promotion $promotion, int $productId, array $categoryIds): bool
    {
        return match ($promotion->applies_to) {
            'products' => in_array($productId, array_map('intval', (array) $promotion->product_ids), true),
            'category' => $promotion->category_id && array_intersect($this->treeOf($promotion->category_id), $categoryIds),
            default => true,
        };
    }

    /** @return array<int, list<int>> product id => category ids */
    private function categoriesOf(array $productIds): array
    {
        return DB::table('category_product')->whereIn('product_id', $productIds)->get(['product_id', 'category_id'])
            ->groupBy('product_id')->map(fn ($rows) => $rows->pluck('category_id')->map(fn ($id) => (int) $id)->all())->all();
    }

    /** A category and everything below it (a deal on "Living" covers "Cushions"). */
    private function treeOf(int $categoryId): array
    {
        return $this->categoryTree[$categoryId] ??= ($category = Category::find($categoryId)) ? $category->descendantIds() : [];
    }
}
