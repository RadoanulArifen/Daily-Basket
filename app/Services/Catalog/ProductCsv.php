<?php

namespace App\Services\Catalog;

/** Column layout shared by the product CSV export and import. */
final class ProductCsv
{
    public const OPTIONS = 3;

    /** @return list<string> */
    public static function headers(): array
    {
        $headers = [
            'handle', 'name_en', 'name_ar', 'short_description_en', 'short_description_ar', 'description_en', 'description_ar',
            'status', 'featured', 'brand', 'categories',
            'seo_title_en', 'seo_title_ar', 'seo_description_en', 'seo_description_ar',
        ];
        for ($i = 1; $i <= self::OPTIONS; $i++) {
            array_push($headers, "option{$i}_name_en", "option{$i}_name_ar", "option{$i}_value_en", "option{$i}_value_ar");
        }

        return [...$headers, 'sku', 'barcode', 'price', 'compare_at_price', 'cost_price', 'weight_grams',
            'track_inventory', 'allow_backorder', 'stock', 'variant_active', 'image_urls'];
    }

    public static function bool(?string $value, bool $default): bool
    {
        $value = mb_strtolower(trim((string) $value));

        return $value === '' ? $default : in_array($value, ['1', 'yes', 'y', 'true', 'on', 'نعم'], true);
    }

    /** The example line of the downloadable template, one value per header (empty = not used). */
    public static function exampleRow(): array
    {
        return array_merge(array_fill_keys(self::headers(), ''), [
            'handle' => 'example-cushion', 'name_en' => 'Example cushion', 'name_ar' => 'وسادة مثال', 'status' => 'draft',
            'categories' => 'cushions', 'option1_name_en' => 'Colour', 'option1_name_ar' => 'اللون', 'option1_value_en' => 'Red',
            'option1_value_ar' => 'أحمر', 'sku' => 'EX-RED', 'price' => '150.00', 'stock' => '10',
        ]);
    }
}
