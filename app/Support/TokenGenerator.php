<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Random tokens used by the shop (never ids or counters). Each kind has its own generator on
 * purpose — they live in different places and lifetimes:
 *   - CSV import files: importToken() below (UUID v4, deleted after one import)
 *   - carts: Str::random(40) in CartService (in an encrypted, signed cookie)
 *   - order links: Str::random(48) in PlaceOrder (orders.access_token, never guessable)
 *   - password resets: Laravel's password broker (hashed in the database, expire)
 */
final class TokenGenerator
{
    /** Token for a CSV import kept between the preview and the import (a UUID v4). */
    public static function importToken(): string
    {
        return Str::uuid()->toString();
    }

    /** True when the value looks like a token made by importToken(). */
    public static function isImportToken(mixed $value): bool
    {
        return is_string($value) && Str::isUuid($value);
    }
}
