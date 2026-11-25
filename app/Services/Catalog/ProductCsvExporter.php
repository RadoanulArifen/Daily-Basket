<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * All products as a spreadsheet: one row per variant. Product details and option
 * names are on each product's first row only, which keeps the sheet readable.
 */
class ProductCsvExporter
{
    public function download(): StreamedResponse
    {
        $filename = 'products-'.now(config('store.display_timezone'))->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 marker so Excel shows Arabic correctly
            fputcsv($out, ProductCsv::headers());

            Product::query()->with(['options.values', 'variants.optionValues', 'categories:id,slug', 'media'])
                ->orderBy('id')->chunk(200, function ($products) use ($out) {
                    foreach ($products as $product) {
                        foreach ($product->variants as $i => $variant) {
                            fputcsv($out, $this->row($product, $variant, $i === 0));
                        }
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function row(Product $product, ProductVariant $variant, bool $first): array
    {
        $t = fn (string $attr, string $locale) => $first ? (string) $product->getTranslation($attr, $locale, false) : '';
        $row = [
            'handle' => $product->slug,
            'name_en' => $t('name', 'en'), 'name_ar' => $t('name', 'ar'),
            'short_description_en' => $t('short_description', 'en'), 'short_description_ar' => $t('short_description', 'ar'),
            'description_en' => $t('description', 'en'), 'description_ar' => $t('description', 'ar'),
            'status' => $first ? $product->status->value : '',
            'featured' => $first ? ($product->is_featured ? 'yes' : 'no') : '',
            'brand' => $first ? (string) $product->brand : '',
            'categories' => $first ? $product->categories->pluck('slug')->implode('|') : '',
            'seo_title_en' => $t('seo_title', 'en'), 'seo_title_ar' => $t('seo_title', 'ar'),
            'seo_description_en' => $t('seo_description', 'en'), 'seo_description_ar' => $t('seo_description', 'ar'),
        ];

        $values = $variant->optionValues->keyBy('product_option_id');
        for ($i = 1; $i <= ProductCsv::OPTIONS; $i++) {
            $option = $product->options->get($i - 1);
            $value = $option ? $values->get($option->id) : null;
            $row["option{$i}_name_en"] = $first && $option ? $option->getTranslation('name', 'en', false) : '';
            $row["option{$i}_name_ar"] = $first && $option ? $option->getTranslation('name', 'ar', false) : '';
            $row["option{$i}_value_en"] = $value?->getTranslation('value', 'en', false) ?? '';
            $row["option{$i}_value_ar"] = $value?->getTranslation('value', 'ar', false) ?? '';
        }

        return array_values($row + [
            'sku' => (string) $variant->sku,
            'barcode' => (string) $variant->barcode,
            'price' => $variant->price->toDecimal(),
            'compare_at_price' => $variant->compare_at_price?->toDecimal() ?? '',
            'cost_price' => $variant->cost_price?->toDecimal() ?? '',
            'weight_grams' => (string) ($variant->weight_grams ?? ''),
            'track_inventory' => $variant->track_inventory ? 'yes' : 'no',
            'allow_backorder' => $variant->allow_backorder ? 'yes' : 'no',
            'stock' => (string) $variant->stock_quantity,
            'variant_active' => $variant->is_active ? 'yes' : 'no',
            'image_urls' => $first ? $product->media->map(fn ($m) => $m->absoluteUrl('large'))->implode(' ') : '',
        ]);
    }
}
