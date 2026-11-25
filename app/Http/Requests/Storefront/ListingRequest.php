<?php

namespace App\Http\Requests\Storefront;

use App\Services\Catalog\StorefrontCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shop filters from the URL. Invalid values are ignored rather than shown as errors,
 * because they usually come from old or hand-edited links.
 */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    /** @return array{sort: ?string, min: ?string, max: ?string, in_stock: bool, sale: bool, q: string} */
    public function filters(): array
    {
        $number = fn ($v) => is_numeric($v) && $v >= 0 && $v < 10_000_000 ? (string) $v : null;
        $sort = $this->query('sort');

        return [
            'sort' => in_array($sort, StorefrontCatalog::SORTS, true) ? $sort : null,
            'min' => $number($this->query('min')),
            'max' => $number($this->query('max')),
            'in_stock' => $this->boolean('in_stock'),
            'sale' => $this->boolean('sale'),
            'q' => mb_substr(trim((string) $this->query('q', '')), 0, 100),
            // Furnivo listing filters.
            'cats' => array_slice(array_filter(array_map('intval', (array) $this->query('cat', []))), 0, 30),
            'brands' => array_slice(array_filter(array_map('intval', (array) $this->query('brand', []))), 0, 30),
            'colours' => array_values(array_intersect((array) $this->query('colour', []), array_keys(\App\Services\Catalog\ProductFacets::COLOURS))),
            'attrs' => collect(\App\Services\Catalog\ProductFacets::GROUPS)
                ->mapWithKeys(fn ($group, $id) => [$id => array_values(array_intersect((array) $this->query($id, []), array_keys($group['options'])))])
                ->filter()->all(),
            'availability' => in_array($this->query('availability'), ['in_stock', 'made_to_order'], true) ? $this->query('availability') : null,
            'band' => array_key_exists((string) $this->query('band'), \App\Services\Catalog\ProductFacets::PRICE_BANDS) ? $this->query('band') : null,
        ];
    }
}
