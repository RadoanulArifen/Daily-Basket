<?php

namespace App\Support;

/**
 * The state of public/storage, through which uploaded images are served.
 *
 * On Windows the link often exists as an entry while pointing nowhere (`storage:link` can
 * fail to create it without administrator rights, leaving a stale entry behind), so its mere
 * presence proves nothing — what counts is whether a file can be read through it.
 */
class StorageLink
{
    public const MISSING = 'missing';          // nothing at public/storage

    public const BROKEN = 'broken';            // an entry exists but does not lead anywhere

    public const WRONG_TARGET = 'wrong-target'; // leads somewhere, but not to the uploads

    public const OK = 'ok';

    public static function state(): string
    {
        $link = public_path('storage');
        $target = storage_path('app'.DIRECTORY_SEPARATOR.'public');
        clearstatcache(true, $link);

        if (! is_link($link) && ! file_exists($link)) {
            return self::MISSING;
        }

        if (is_file($link)) {
            return self::BROKEN;   // a plain file sits in the way
        }

        // realpath() is the reliable test on Windows: PHP cannot stat a junction made by
        // "mklink /J" (is_dir/is_link both report false) even though the junction works and
        // the web server serves files through it.
        $resolved = @realpath($link);
        $real = @realpath($target);

        if ($resolved === false) {
            return is_dir($link) ? self::OK : self::BROKEN;   // leads nowhere
        }

        if ($real !== false && ! self::samePath($resolved, $real)) {
            return self::WRONG_TARGET;
        }

        return self::OK;
    }

    private static function samePath(string $a, string $b): bool
    {
        $a = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $a), DIRECTORY_SEPARATOR);
        $b = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $b), DIRECTORY_SEPARATOR);

        return windows_os() ? strcasecmp($a, $b) === 0 : $a === $b;
    }

    /** True when PHP itself cannot read through the link, though the web server can. */
    public static function opaqueToPhp(): bool
    {
        return self::state() === self::OK && ! is_dir(public_path('storage'));
    }

    /** Images can be served by the web server directly; no PHP fallback needed. */
    public static function works(): bool
    {
        return is_dir(public_path('storage'));
    }
}
