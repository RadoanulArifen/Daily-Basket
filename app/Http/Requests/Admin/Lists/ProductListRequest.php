<?php

namespace App\Http\Requests\Admin\Lists;

use App\Enums\ProductStatus;
use App\Http\Controllers\Admin\Catalog\ProductController;
use Illuminate\Validation\Rule;

/** Filters of Admin → Products. */
class ProductListRequest extends ListFilterRequest
{
    protected function sorts(): array
    {
        return ProductController::SORTS;
    }

    protected function filters(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category' => ['nullable', 'integer'],
            'stock' => ['nullable', Rule::in(['in', 'low', 'out'])],
            'featured' => ['nullable', Rule::in(['yes'])],
        ];
    }
}
