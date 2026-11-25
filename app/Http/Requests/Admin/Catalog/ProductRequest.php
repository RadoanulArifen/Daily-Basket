<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the whole product form: details, images, categories, options and variants.
 *
 * Options and their values carry a "key" chosen by the browser (existing id or "new-…"),
 * and each variant lists the keys of its option values.
 */
class ProductRequest extends FormRequest
{
    public const MAX_OPTIONS = 3;

    public const MAX_VALUES = 30;

    public const MAX_VARIANTS = 100;

    private const MONEY = ['numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'];

    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $bool = fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);

        $variants = collect((array) $this->input('variants', []))->map(function ($variant) use ($bool) {
            $variant = (array) $variant;

            foreach (['track_inventory', 'allow_backorder', 'is_active'] as $flag) {
                $variant[$flag] = $bool($variant[$flag] ?? ($flag !== 'allow_backorder'));
            }

            foreach (['compare_at_price', 'cost_price', 'sku', 'barcode', 'weight_grams', 'media_id', 'id'] as $field) {
                if (($variant[$field] ?? '') === '') {
                    $variant[$field] = null;
                }
            }

            $variant['sku'] = isset($variant['sku']) ? trim($variant['sku']) : null;
            $variant['option_keys'] = array_values((array) ($variant['option_keys'] ?? []));
            $variant['stock_quantity'] = ($variant['stock_quantity'] ?? '') === '' ? 0 : $variant['stock_quantity'];

            return $variant;
        })->values()->all();

        $this->merge([
            'is_featured' => $bool($this->input('is_featured', false)),
            'slug' => $this->filled('slug') ? strtolower(trim($this->input('slug'))) : null,
            'options' => array_values((array) $this->input('options', [])),
            'variants' => $variants,
            'category_ids' => array_values(array_filter((array) $this->input('category_ids', []))),
            'media_ids' => array_values(array_filter((array) $this->input('media_ids', []))),
        ]);
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:200'],
            'name.ar' => ['nullable', 'string', 'max:200'],
            'short_description.*' => ['nullable', 'string', 'max:5000'],
            'description.*' => ['nullable', 'string', 'max:50000'],
            'dimensions.*' => ['nullable', 'string', 'max:20000'],
            'delivery_returns.*' => ['nullable', 'string', 'max:20000'],
            'trust_badges.*' => ['nullable', 'string', 'max:400'],
            'seo_title.*' => ['nullable', 'string', 'max:70'],
            'seo_description.*' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique(Product::class)->ignore($product)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
            'brand' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            // "Configure your piece" — the choices shown on the product page.
            'configuration' => ['nullable', 'array', 'max:8'],
            'configuration.*.label' => ['nullable', 'string', 'max:40'],
            'configuration.*.values' => ['nullable', 'string', 'max:400'],

            'category_ids' => ['array', 'max:50'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists(Category::class, 'id')],
            'media_ids' => ['array', 'max:20'],
            'media_ids.*' => ['integer', 'distinct', Rule::exists(Media::class, 'id')],

            'options' => ['array', 'max:'.self::MAX_OPTIONS],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.key' => ['required', 'string', 'max:40', 'distinct'],
            'options.*.name.en' => ['required', 'string', 'max:50'],
            'options.*.name.ar' => ['nullable', 'string', 'max:50'],
            'options.*.values' => ['required', 'array', 'min:1', 'max:'.self::MAX_VALUES],
            'options.*.values.*.id' => ['nullable', 'integer'],
            'options.*.values.*.key' => ['required', 'string', 'max:40'],
            'options.*.values.*.value.en' => ['required', 'string', 'max:60'],
            'options.*.values.*.value.ar' => ['nullable', 'string', 'max:60'],

            'variants' => ['required', 'array', 'min:1', 'max:'.self::MAX_VARIANTS],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.option_keys' => ['array'],
            'variants.*.option_keys.*' => ['string'],
            'variants.*.price' => ['required', ...self::MONEY],
            'variants.*.compare_at_price' => ['nullable', ...self::MONEY, 'gt:variants.*.price'],
            'variants.*.cost_price' => ['nullable', ...self::MONEY],
            'variants.*.sku' => [
                'nullable', 'string', 'max:64', 'distinct:ignore_case',
                Rule::unique('product_variants', 'sku')
                    ->whereNull('deleted_at')
                    ->when($product, fn ($rule) => $rule->whereNot('product_id', $product->id)),
            ],
            'variants.*.barcode' => ['nullable', 'string', 'max:64'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.track_inventory' => ['boolean'],
            'variants.*.allow_backorder' => ['boolean'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.stock_quantity' => ['integer', 'min:-1000000', 'max:1000000'],
            'variants.*.original_stock' => ['nullable', 'integer'],
            'variants.*.media_id' => ['nullable', 'integer', Rule::in($this->input('media_ids', []))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $options = $this->input('options', []);
            $variants = $this->input('variants', []);

            // Option names must differ, and so must the values inside each option.
            $names = collect($options)->map(fn ($o) => mb_strtolower(trim($o['name']['en'])));
            if ($names->duplicates()->isNotEmpty()) {
                $validator->errors()->add('options', __('admin.products.errors.duplicate_option'));
            }

            $keyToOption = [];
            foreach ($options as $i => $option) {
                $values = collect($option['values'])->map(fn ($v) => mb_strtolower(trim($v['value']['en'])));
                if ($values->duplicates()->isNotEmpty()) {
                    $validator->errors()->add("options.{$i}.values", __('admin.products.errors.duplicate_value', ['option' => $option['name']['en']]));
                }
                foreach ($option['values'] as $value) {
                    if (isset($keyToOption[$value['key']])) {
                        $validator->errors()->add('options', __('admin.products.errors.invalid_variants'));
                    }
                    $keyToOption[$value['key']] = $i;
                }
            }

            // Without options: exactly one variant. With options: one value per option, no duplicate combinations.
            if (! $options) {
                if (count($variants) !== 1 || ! empty($variants[0]['option_keys'])) {
                    $validator->errors()->add('variants', __('admin.products.errors.invalid_variants'));
                }

                return;
            }

            $combinations = [];
            foreach ($variants as $k => $variant) {
                $keys = $variant['option_keys'];
                $optionIndexes = array_map(fn ($key) => $keyToOption[$key] ?? null, $keys);

                if (count($keys) !== count($options) || in_array(null, $optionIndexes, true) || count(array_unique($optionIndexes)) !== count($options)) {
                    $validator->errors()->add("variants.{$k}", __('admin.products.errors.invalid_variants'));

                    continue;
                }

                sort($keys);
                $signature = implode('|', $keys);
                if (isset($combinations[$signature])) {
                    $validator->errors()->add("variants.{$k}", __('admin.products.errors.duplicate_variant'));
                }
                $combinations[$signature] = true;
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'name.en' => __('admin.products.fields.name').' (English)',
            'variants.*.price' => __('admin.products.fields.price'),
            'variants.*.compare_at_price' => __('admin.products.fields.compare_at_price'),
            'variants.*.cost_price' => __('admin.products.fields.cost_price'),
            'variants.*.sku' => __('admin.products.fields.sku'),
            'variants.*.stock_quantity' => __('admin.products.fields.stock'),
            'variants.*.weight_grams' => __('admin.products.fields.weight'),
            'options.*.name.en' => __('admin.products.fields.option_name'),
            'options.*.values.*.value.en' => __('admin.products.fields.option_value'),
            'slug' => __('admin.common.url_handle'),
        ];
    }

    public function messages(): array
    {
        return [
            'variants.*.compare_at_price.gt' => __('admin.products.errors.compare_at'),
            'variants.*.sku.unique' => __('admin.products.errors.sku_taken'),
            'variants.*.sku.distinct' => __('admin.products.errors.sku_repeated'),
            'slug.regex' => __('admin.common.url_handle_invalid'),
        ];
    }
}
