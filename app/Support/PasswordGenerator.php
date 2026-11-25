<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Strong random passwords for accounts created from the command line (seeders, commands). */
final class PasswordGenerator
{
    public const LENGTH = 16;

    /** Letters, numbers and symbols; shown once to the person installing the shop. */
    public static function secure(int $length = self::LENGTH): string
    {
        return Str::password($length);
    }
}
