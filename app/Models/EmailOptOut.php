<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Someone who asked not to receive reminder emails. */
class EmailOptOut extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['email'];

    public static function has(string $email): bool
    {
        return static::query()->where('email', mb_strtolower(trim($email)))->exists();
    }
}
