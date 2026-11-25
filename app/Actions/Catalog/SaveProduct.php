<?php

namespace App\Actions\Catalog;

use App\Enums\InventoryReason;
use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a product from validated ProductRequest data, all in one transaction.
 */
class SaveProduct
{
    public function __construct(private InventoryService $inventory) {}

    public function handle(array $data, ?Product $product = null, ?Admin $admin = null): Product
    {
        return DB::transaction(function () use ($data, $product, $admin) {
            $product ??= new Product;

            $this->saveDetails($product, $data);

            $product->categories()->sync(collect($data['category_ids'] ?? [])->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));
            $product->media()->sync(collect($data['media_ids'] ?? [])->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

            $valueIdsByKey = $this->saveOptions($product, $data['options'] ?? []);
            $this->saveVariants($product, $data['variants'], $valueIdsByKey, $admin);

            return $product->refresh();
        });
    }

    private function saveDetails(Product $product, array $data): void
    {
        $status = ProductStatus::from($data['status']);

        // Product-page texts: only when the form sent them (CSV imports leave them alone).
        foreach (['dimensions', 'delivery_returns', 'trust_badges'] as $field) {
            if (array_key_exists($field, $data)) {
                $product->setAttribute($field, array_filter((array) $data[$field]));
            }
        }

        $product->fill([
            'name' => array_filter($data['name']),
            'short_description' => array_filter($data['short_description'] ?? []),
            'description' => array_filter($data['description'] ?? []),
            'seo_title' => array_filter($data['seo_title'] ?? []),
            'seo_description' => array_filter($data['seo_description'] ?? []),
            'status' => $status,
            'is_featured' => $data['is_featured'] ?? false,
            'configuration' => self::configuration($data['configuration'] ?? null),

            // The brand is chosen from the list; its name is kept alongside so CSV exports
            // and anything still reading the plain field stay correct.
            'brand_id' => $data['brand_id'] ?? null,
            'brand' => filled($data['brand_id'] ?? null)
                ? \App\Models\Brand::query()->whereKey($data['brand_id'])->value('name')
                : ($data['brand'] ?? null),
        ]);

        $product->slug = $data['slug'] ?? null
            ?: ($product->exists ? $product->slug : Slug::unique(Product::class, $data['name']['en'], null, 'product'));

        // Remember when a product first went live ("New in" sorting uses this).
        if ($status === ProductStatus::Active && ! $product->published_at) {
            $product->published_at = now();
        }

        $product->save();
    }

    /**
     * @return array<string, int> browser key => option value id
     */
    private function saveOptions(Product $product, array $options): array
    {
        $existing = $product->options()->with('values')->get()->keyBy('id');
        $keptOptionIds = [];
        $valueIdsByKey = [];

        foreach (array_values($options) as $position => $input) {
            /** @var ProductOption $option */
            $option = isset($input['id']) && $existing->has($input['id']) ? $existing->get($input['id']) : new ProductOption(['product_id' => $product->id]);
            $option->fill(['name' => array_filter($input['name']), 'position' => $position])->save();
            $keptOptionIds[] = $option->id;

            $existingValues = $option->relationLoaded('values') ? $option->values->keyBy('id') : collect();
            $keptValueIds = [];

            foreach (array_values($input['values']) as $valuePosition => $valueInput) {
                /** @var ProductOptionValue $value */
                $value = isset($valueInput['id']) && $existingValues->has($valueInput['id'])
                    ? $existingValues->get($valueInput['id'])
                    : new ProductOptionValue(['product_option_id' => $option->id]);

                $value->fill(['value' => array_filter($valueInput['value']), 'position' => $valuePosition])->save();

                $keptValueIds[] = $value->id;
                $valueIdsByKey[$valueInput['key']] = $value->id;
            }

            $option->values()->whereNotIn('id', $keptValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $keptOptionIds)->delete();

        return $valueIdsByKey;
    }

    private function saveVariants(Product $product, array $variants, array $valueIdsByKey, ?Admin $admin): void
    {
        $existing = $product->variants()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($variants) as $position => $input) {
            $isNew = ! (isset($input['id']) && $existing->has($input['id']));

            /** @var ProductVariant $variant */
            $variant = $isNew ? new ProductVariant(['product_id' => $product->id]) : $existing->get($input['id']);

            $variant->fill([
                'sku' => $input['sku'] ?? null,
                'barcode' => $input['barcode'] ?? null,
                'price' => (string) $input['price'],
                'compare_at_price' => isset($input['compare_at_price']) ? (string) $input['compare_at_price'] : null,
                'cost_price' => isset($input['cost_price']) ? (string) $input['cost_price'] : null,
                'weight_grams' => $input['weight_grams'] ?? null,
                'track_inventory' => $input['track_inventory'],
                'allow_backorder' => $input['allow_backorder'],
                'is_active' => $input['is_active'],
                'media_id' => $input['media_id'] ?? null,
                'position' => $position,
            ])->save();

            $variant->optionValues()->sync(
                collect($input['option_keys'] ?? [])->map(fn ($key) => $valueIdsByKey[$key])->all()
            );

            $this->saveStock($variant, $input, $isNew, $admin);
            $keptIds[] = $variant->id;
        }

        // Removed variants are soft-deleted so past orders keep their reference.
        $product->variants()->whereNotIn('id', $keptIds)->get()->each->delete();
    }

    /**
     * New variants get their starting stock. For existing ones we apply only the
     * difference the admin typed, so a sale made while the form was open isn't lost.
     */
    private function saveStock(ProductVariant $variant, array $input, bool $isNew, ?Admin $admin): void
    {
        if (! $input['track_inventory']) {
            return;
        }

        $typed = (int) $input['stock_quantity'];

        if ($isNew) {
            $this->inventory->set($variant, $typed, InventoryReason::Initial, admin: $admin);

            return;
        }

        $original = array_key_exists('original_stock', $input) && $input['original_stock'] !== null
            ? (int) $input['original_stock']
            : $variant->stock_quantity;

        $this->inventory->adjust($variant, $typed - $original, InventoryReason::Edited, admin: $admin, allowNegative: true);
    }

    /**
     * Turn the admin's rows into the product page's option list.
     *
     * A row is a label and its values, comma separated. A value can carry a colour for the
     * swatch by writing it as "Walnut #6b4423"; without one it is shown as a plain pill.
     *
     * @param  array<int, array{label?: ?string, values?: ?string}>|null  $rows
     */
    public static function configuration(?array $rows): ?array
    {
        if ($rows === null) {
            return null;
        }

        $groups = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $raw = collect(explode(',', (string) ($row['values'] ?? '')))->map(fn ($v) => trim($v))->filter();

            if ($label === '' || $raw->isEmpty()) {
                continue;
            }

            $values = [];
            $swatches = [];

            foreach ($raw as $value) {
                if (preg_match('/^(.*?)\s+(#[0-9a-fA-F]{3,8})$/', $value, $m)) {
                    $values[] = trim($m[1]);
                    $swatches[trim($m[1])] = $m[2];
                } else {
                    $values[] = $value;
                }
            }

            $groups[] = array_filter([
                'label' => $label,
                'values' => $values,
                'swatches' => $swatches ?: null,
            ]);
        }

        return $groups ?: null;
    }
}
