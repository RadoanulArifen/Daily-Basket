<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['email', 'locale', 'source', 'unsubscribed_at'];

    protected $attributes = ['locale' => 'en', 'source' => null, 'unsubscribed_at' => null];

    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }
}
