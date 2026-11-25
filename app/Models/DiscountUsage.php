<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountUsage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['discount_id', 'order_id', 'email'];
}
