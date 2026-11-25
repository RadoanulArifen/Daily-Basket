<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\AdminController;
use App\Support\Search;
use App\Enums\InventoryReason;
use App\Http\Requests\Admin\Catalog\InventoryAdjustmentRequest;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends AdminController
{
    public function index(\App\Http\Requests\Admin\Lists\InventoryListRequest $request, SettingsService $settings): View
    {
        $this->authorizeArea('inventory');
        $filters = $request->validated();

        $threshold = $settings->lowStockThreshold();

        $variants = ProductVariant::query()
            ->where('track_inventory', true)
            ->whereHas('product', fn ($q) => $q->whereNull('products.deleted_at'))
            ->with(['product.media', 'optionValues', 'movements' => fn ($q) => $q->with('admin:id,name')->limit(5)])
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $q->where(fn ($w) => Search::like($w, 'sku', $term)
                    ->orWhereHas('product', fn ($p) => Search::likeAny($p, ['name->en', 'name->ar'], $term)));
            })
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->whereBetween('stock_quantity', [1, $threshold]))
            ->when(($filters['stock'] ?? null) === 'out', fn ($q) => $q->where('stock_quantity', '<=', 0))
            ->orderBy('stock_quantity')
            ->orderBy('product_id')
            ->paginate((int) config('store.pagination.inventory', 30))
            ->withQueryString();

        return view('admin.catalog.inventory.index', [
            'variants' => $variants,
            'filters' => $filters,
            'threshold' => $threshold,
            'reasons' => InventoryReason::manual(),
        ]);
    }

    public function adjust(InventoryAdjustmentRequest $request, ProductVariant $variant, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeArea('inventory');
        $data = $request->validated();
        $reason = InventoryReason::from($data['reason']);
        $admin = $request->user('admin');
        $quantity = (int) $data['quantity'];

        match ($data['mode']) {
            'add' => $inventory->adjust($variant, $quantity, $reason, $data['note'] ?? null, $admin),
            'remove' => $inventory->adjust($variant, -$quantity, $reason, $data['note'] ?? null, $admin),
            'set' => $inventory->set($variant, $quantity, $reason, $data['note'] ?? null, $admin),
        };

        return back()->with('success', __('admin.inventory.updated', ['quantity' => $variant->stock_quantity]));
    }

    public function threshold(Request $request, SettingsService $settings): RedirectResponse
    {
        $this->authorizeArea('inventory');
        $data = $request->validate(['low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000']]);
        $settings->set('inventory.low_stock_threshold', (int) $data['low_stock_threshold']);

        return back()->with('success', __('admin.settings.saved'));
    }
}
