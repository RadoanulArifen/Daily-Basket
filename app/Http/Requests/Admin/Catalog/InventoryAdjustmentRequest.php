<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\InventoryReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['add', 'remove', 'set'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required', Rule::in(array_map(fn ($r) => $r->value, InventoryReason::manual()))],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['quantity' => __('admin.inventory.quantity')];
    }
}
