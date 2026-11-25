<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;

/** The data the admin product editor (Alpine) starts from: options, variants and photos. */
class ProductFormState
{
    /**
     * Everything the Alpine product editor needs, as plain arrays.
     * On a failed save, the submitted values (old input) are restored instead.
     */
    public function build(?Product $product): array
    {
        $product?->load(['options.values', 'variants.optionValues', 'media']);

        $options = old('options', $product?->options->map(fn (ProductOption $o) => [
            'id' => $o->id,
            'key' => 'o'.$o->id,
            'name' => ['en' => $o->getTranslation('name', 'en', false), 'ar' => $o->getTranslation('name', 'ar', false)],
            'values' => $o->values->map(fn ($v) => [
                'id' => $v->id,
                'key' => 'v'.$v->id,
                'value' => ['en' => $v->getTranslation('value', 'en', false), 'ar' => $v->getTranslation('value', 'ar', false)],
            ])->all(),
        ])->all() ?? []);

        $variants = old('variants', $product?->variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'option_keys' => $v->optionValues->map(fn ($value) => 'v'.$value->id)->all(),
            'price' => $v->price->toDecimal(),
            'compare_at_price' => $v->compare_at_price?->toDecimal(),
            'cost_price' => $v->cost_price?->toDecimal(),
            'sku' => $v->sku,
            'barcode' => $v->barcode,
            'weight_grams' => $v->weight_grams,
            'track_inventory' => $v->track_inventory,
            'allow_backorder' => $v->allow_backorder,
            'is_active' => $v->is_active,
            'stock_quantity' => $v->stock_quantity,
            'original_stock' => $v->stock_quantity,
            'media_id' => $v->media_id,
        ])->all() ?? []);

        $mediaIds = old('media_ids', $product?->media->pluck('id')->all() ?? []);
        $media = \App\Models\Media::query()->whereKey($mediaIds)->get()->keyBy('id');

        return [
            'options' => array_values($options),
            'variants' => array_values($variants),
            'media' => collect($mediaIds)->map(fn ($id) => $media->get($id)?->toPickerArray())->filter()->values()->all(),
            'uploadUrl' => route('admin.media.store'),
            'libraryUrl' => route('admin.media.index', ['format' => 'json']),
            'baseCurrency' => config('store.base_currency'),
        ];
    }
}
