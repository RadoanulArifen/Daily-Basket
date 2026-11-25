<?php

namespace App\Http\Requests\Admin\Lists;

use App\Http\Controllers\Admin\Insights\CustomerController;
use Illuminate\Validation\Rule;

/** Filters of Admin → Customers. */
class CustomerListRequest extends ListFilterRequest
{
    protected function sorts(): array
    {
        return CustomerController::SORTS;
    }

    protected function filters(): array
    {
        return [
            'segment' => ['nullable', Rule::in(CustomerController::SEGMENTS)],
            'country' => ['nullable', Rule::in(array_keys(\App\Support\Countries::NAMES))],
        ];
    }
}
