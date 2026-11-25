<?php

namespace App\Services\Catalog;

use App\Actions\Catalog\SaveProduct;
use App\Http\Requests\Admin\Catalog\ProductRequest;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Creates and updates products from a spreadsheet. Each product goes through the same
 * validation (ProductRequest) and save logic (SaveProduct) as the admin product form.
 *
 * A product's rows describe all of its variants: variants missing from the file are removed
 * from the product (kept for past orders). Images aren't imported — add them in the admin.
 */
class ProductCsvImporter
{
    public const MAX_ROWS = 5000;

    public function __construct(private SaveProduct $save) {}

    /**
     * Check the file without saving anything.
     *
     * @return array{create: int, update: int, variants: int, errors: list<string>, ignored: list<string>}
     */
    public function preview(string $path, ?Admin $admin): array
    {
        [$plans, $errors, $ignored] = $this->plan($path, $admin);

        return [
            'create' => collect($plans)->whereNull('product')->count(),
            'update' => collect($plans)->whereNotNull('product')->count(),
            'variants' => collect($plans)->sum(fn ($p) => count($p['data']['variants'])),
            'errors' => $errors,
            'ignored' => $ignored,
        ];
    }

    /** Import everything, or nothing if any row has a problem. */
    public function import(string $path, ?Admin $admin): array
    {
        [$plans, $errors] = $this->plan($path, $admin);

        if ($errors) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        DB::transaction(function () use ($plans, $admin) {
            foreach ($plans as $plan) {
                $this->save->handle($plan['data'], $plan['product'], $admin);
            }
        });

        return ['create' => collect($plans)->whereNull('product')->count(), 'update' => collect($plans)->whereNotNull('product')->count()];
    }

    /** @return array{0: list<array{product: ?Product, data: array}>, 1: list<string>, 2: list<string>} */
    private function plan(string $path, ?Admin $admin): array
    {
        [$rows, $ignored] = $this->read($path);
        $categories = Category::query()->pluck('id', 'slug');
        $groups = [];
        foreach ($rows as $line => $row) {
            $handle = trim($row['handle'] ?? '') ?: 'row-'.$line;
            $groups[$handle][$line] = $row;
        }

        $plans = [];
        $errors = [];
        foreach ($groups as $handle => $productRows) {
            try {
                $product = str_starts_with($handle, 'row-') ? null : Product::query()->where('slug', $handle)->with(['options.values', 'variants.optionValues'])->first();
                $data = $this->productData($handle, $productRows, $product, $categories);
                $plans[] = ['product' => $product, 'data' => $this->validate($data, $product, $admin, array_keys($productRows))];
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $errors[] = $message;
                    }
                }
            }
        }

        return [$plans, array_values(array_unique($errors)), $ignored];
    }

    /** @return array{0: array<int, array<string, string>>, 1: list<string>} rows keyed by spreadsheet line number */
    private function read(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('Could not open the file.');
        }

        $first = (string) fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';   // Excel in some regions uses ;
        $headers = array_map(fn ($h) => mb_strtolower(trim($h)), str_getcsv($first, $delimiter));

        foreach (['handle', 'name_en', 'price'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw ValidationException::withMessages(['file' => __('admin.import.missing_column', ['column' => $required])]);
            }
        }

        $known = ProductCsv::headers();
        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($cells === [null] || implode('', array_map('trim', $cells)) === '') {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                throw ValidationException::withMessages(['file' => __('admin.import.too_many_rows', ['max' => self::MAX_ROWS])]);
            }
            $rows[$line] = array_combine($headers, array_map(fn ($c) => trim((string) $c), array_pad(array_slice($cells, 0, count($headers)), count($headers), '')));
        }
        fclose($handle);

        return [$rows, array_values(array_diff($headers, $known))];
    }

    private function productData(string $handle, array $rows, ?Product $product, $categories): array
    {
        $first = reset($rows);
        $firstLine = array_key_first($rows);
        $at = fn (int $line) => __('admin.import.row', ['line' => $line]);
        $pair = fn (string $field) => array_filter(['en' => $first["{$field}_en"] ?? '', 'ar' => $first["{$field}_ar"] ?? ''], fn ($v) => $v !== '');

        if (! $product && Product::onlyTrashed()->where('slug', $handle)->exists()) {
            throw ValidationException::withMessages(['handle' => $at($firstLine).' '.__('admin.import.handle_deleted', ['handle' => $handle])]);
        }

        // Categories by their URL handle, e.g. "living|gifts".
        $categoryIds = [];
        foreach (array_filter(array_map('trim', explode('|', $first['categories'] ?? ''))) as $slug) {
            if (! $categories->has($slug)) {
                throw ValidationException::withMessages(['categories' => $at($firstLine).' '.__('admin.import.unknown_category', ['slug' => $slug])]);
            }
            $categoryIds[] = $categories[$slug];
        }

        // Options: names from the first row, values collected from every row (reusing existing ids).
        $options = [];
        for ($i = 1; $i <= ProductCsv::OPTIONS; $i++) {
            $name = $first["option{$i}_name_en"] ?? '';
            if ($name === '') {
                continue;
            }
            $existing = $product?->options->first(fn ($o) => mb_strtolower($o->getTranslation('name', 'en', false)) === mb_strtolower($name));
            $option = ['id' => $existing?->id, 'key' => $existing ? 'o'.$existing->id : "csv-o{$i}", 'name' => array_filter(['en' => $name, 'ar' => $first["option{$i}_name_ar"] ?? '']), 'values' => [], 'col' => $i];

            foreach ($rows as $row) {
                $valueEn = $row["option{$i}_value_en"] ?? '';
                if ($valueEn === '' || collect($option['values'])->contains(fn ($v) => mb_strtolower($v['value']['en']) === mb_strtolower($valueEn))) {
                    continue;
                }
                $existingValue = $existing?->values->first(fn ($v) => mb_strtolower($v->getTranslation('value', 'en', false)) === mb_strtolower($valueEn));
                $option['values'][] = [
                    'id' => $existingValue?->id,
                    'key' => $existingValue ? 'v'.$existingValue->id : "csv-o{$i}-v".count($option['values']),
                    'value' => array_filter(['en' => $valueEn, 'ar' => $row["option{$i}_value_ar"] ?? '']),
                ];
            }
            $options[] = $option;
        }

        // Variants: matched to existing ones by SKU, then by their option values.
        $variants = [];
        foreach ($rows as $line => $row) {
            $keys = [];
            foreach ($options as $option) {
                $valueEn = mb_strtolower($row["option{$option['col']}_value_en"] ?? '');
                $match = collect($option['values'])->first(fn ($v) => mb_strtolower($v['value']['en']) === $valueEn);
                if (! $match) {
                    throw ValidationException::withMessages(['options' => $at($line).' '.__('admin.import.missing_value', ['option' => $option['name']['en']])]);
                }
                $keys[] = $match['key'];
            }

            $sku = $row['sku'] ?? '';
            $existingVariant = $product?->variants->first(fn ($v) => $sku !== '' && $v->sku === $sku)
                ?? $product?->variants->first(fn ($v) => $v->optionValues->map(fn ($ov) => 'v'.$ov->id)->sort()->values()->all() === collect($keys)->sort()->values()->all());
            $stock = $row['stock'] ?? '';

            $variants[] = [
                'id' => $existingVariant?->id,
                'option_keys' => $keys,
                'price' => $row['price'] ?? '',
                'compare_at_price' => $row['compare_at_price'] ?? '',
                'cost_price' => $row['cost_price'] ?? '',
                'sku' => $sku,
                'barcode' => $row['barcode'] ?? '',
                'weight_grams' => $row['weight_grams'] ?? '',
                'track_inventory' => ProductCsv::bool($row['track_inventory'] ?? null, true) ? '1' : '0',
                'allow_backorder' => ProductCsv::bool($row['allow_backorder'] ?? null, false) ? '1' : '0',
                'is_active' => ProductCsv::bool($row['variant_active'] ?? null, true) ? '1' : '0',
                // Stock in the file is the new total. Empty keeps what's in stock now.
                'stock_quantity' => $stock !== '' ? $stock : (string) ($existingVariant?->stock_quantity ?? 0),
                'original_stock' => $existingVariant ? (string) $existingVariant->stock_quantity : null,
                'media_id' => $existingVariant?->media_id,
            ];
        }

        return [
            'name' => $pair('name') ?: ['en' => $product?->getTranslation('name', 'en', false) ?? ''],
            'short_description' => $pair('short_description'),
            'description' => $pair('description'),
            'seo_title' => $pair('seo_title'),
            'seo_description' => $pair('seo_description'),
            'slug' => str_starts_with($handle, 'row-') ? '' : $handle,
            'status' => ($first['status'] ?? '') ?: ($product?->status->value ?? 'draft'),
            'is_featured' => ProductCsv::bool($first['featured'] ?? null, $product?->is_featured ?? false) ? '1' : '0',
            'brand' => ($first['brand'] ?? '') ?: $product?->brand,
            'category_ids' => $categoryIds,
            'media_ids' => $product?->media()->pluck('media.id')->all() ?? [],
            'options' => array_map(fn ($o) => array_diff_key($o, ['col' => true]), $options),
            'variants' => $variants,
        ];
    }

    /** Run the admin form's own validation, reporting problems by spreadsheet row. */
    private function validate(array $data, ?Product $product, ?Admin $admin, array $lines): array
    {
        $request = ProductRequest::create('/', 'POST', $data);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $admin);
        $route = (new Route('POST', '/', []))->bind($request);
        if ($product) {
            $route->setParameter('product', $product);
        }
        $request->setRouteResolver(fn () => $route);

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $field => $fieldMessages) {
                preg_match('/^variants\.(\d+)/', $field, $m);
                $line = isset($m[1]) ? ($lines[(int) $m[1]] ?? $lines[0]) : $lines[0];
                foreach ($fieldMessages as $message) {
                    $messages[] = __('admin.import.row', ['line' => $line]).' '.$message;
                }
            }
            throw ValidationException::withMessages(['file' => $messages]);
        }

        return $request->validated();
    }
}
