<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'reference' => null,
        'data' => null,
    ];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'data' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
