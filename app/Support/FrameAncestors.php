<?php

namespace App\Support;

/**
 * Which other websites may show the landing page and the storefront inside an iframe — e.g. a
 * marketplace's live-demo frame. Set in .env, space or comma separated:
 *
 *   ALLOW_FRAMING_FROM="https://vibe96.com https://www.vibe96.com"
 *
 * Empty (the default) = only this site itself. The admin panel is never framable (clickjacking).
 * Only well-formed http(s) origins are used; anything else in the setting is ignored.
 */
final class FrameAncestors
{
    /** @return list<string> */
    public static function extra(): array
    {
        $raw = preg_split('/[\s,]+/', (string) config('store.allow_framing_from', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            array_map(fn ($o) => rtrim(strtolower($o), '/'), $raw),
            fn ($o) => (bool) preg_match('#^https?://(\*\.)?[a-z0-9-]+(\.[a-z0-9-]+)*(:\d{1,5})?$#', $o),
        )));
    }

    /** The CSP directive value: 'self' plus the allowed origins. */
    public static function directive(): string
    {
        return implode(' ', ["'self'", ...self::extra()]);
    }

    public static function allowsOthers(): bool
    {
        return self::extra() !== [];
    }
}
