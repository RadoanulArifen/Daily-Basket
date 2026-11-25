<?php

namespace App\Services\Inventory;

use App\Enums\InventoryReason;
use App\Models\Admin;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock numbers change. Each change is logged in inventory_movements.
 * Rows are locked while updating, so two changes at once can't overwrite each other.
 */
class InventoryService
{
    /** Add (positive) or remove (negative) stock. */
    public function adjust(ProductVariant $variant, int $change, InventoryReason $reason, ?string $note = null, ?Admin $admin = null, bool $allowNegative = false): ?InventoryMovement
    {
        if ($change === 0) {
            return null;
        }

        return DB::transaction(function () use ($variant, $change, $reason, $note, $admin, $allowNegative) {
            $locked = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->firstOrFail();
            $after = $locked->stock_quantity + $change;

            if ($after < 0 && ! $allowNegative) {
                throw ValidationException::withMessages([
                    'quantity' => __('admin.inventory.not_enough', ['available' => $locked->stock_quantity]),
                ]);
            }

            $locked->forceFill(['stock_quantity' => $after])->save();
            $variant->setRawAttributes(array_merge($variant->getAttributes(), ['stock_quantity' => $after]), true);

            return InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'change' => $change,
                'quantity_after' => $after,
                'reason' => $reason,
                'note' => $note,
                'admin_id' => $admin?->id,
            ]);
        });
    }

    /** Set stock to an exact number (e.g. after a stock count). */
    public function set(ProductVariant $variant, int $quantity, InventoryReason $reason, ?string $note = null, ?Admin $admin = null): ?InventoryMovement
    {
        return DB::transaction(function () use ($variant, $quantity, $reason, $note, $admin) {
            $current = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->value('stock_quantity');

            return $this->adjust($variant, $quantity - (int) $current, $reason, $note, $admin, allowNegative: true);
        });
    }
}
