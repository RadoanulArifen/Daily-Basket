<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrderRequest extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'name', 'email', 'phone', 'category', 'dimensions',
        'material', 'finish', 'quantity', 'budget', 'timeline', 'message', 'locale',
    ];

    protected $attributes = ['status' => 'new', 'quantity' => 1];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The piece the request is about, however it was described. */
    public function subject(): string
    {
        return $this->product?->name ?: ($this->category ?: __('store.custom.subject_none'));
    }
}
