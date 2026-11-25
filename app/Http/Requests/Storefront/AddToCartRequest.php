<?php

namespace App\Http\Requests\Storefront;

use App\Services\Cart\CartService;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }

    public function attributes(): array
    {
        return ['quantity' => __('store.product.quantity')];
    }
}
