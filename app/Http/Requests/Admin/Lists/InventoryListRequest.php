<?php

namespace App\Http\Requests\Admin\Lists;

use Illuminate\Validation\Rule;

/** Filters of Admin → Products → Inventory. */
class InventoryListRequest extends ListFilterRequest
{
    protected function filters(): array
    {
        return ['stock' => ['nullable', Rule::in(['low', 'out'])]];
    }
}
