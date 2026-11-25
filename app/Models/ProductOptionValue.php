<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductOptionValue extends Model
{
    use HasTranslations;

    public array $translatable = ['value'];

    protected $fillable = ['product_option_id', 'value', 'position'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
