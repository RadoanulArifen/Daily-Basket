<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Slug
{
    /**
     * A URL slug that no other row uses: "brand-red-cushion", then "brand-red-cushion-2", ...
     * Arabic-only names fall back to $fallback (slugs stay Latin for clean URLs).
     *
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model, ?string $source, ?int $ignoreId = null, string $fallback = 'item'): string
    {
        $base = Str::slug((string) $source) ?: $fallback;
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $n = 2;

        while (self::taken($model, $slug, $ignoreId)) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    private static function taken(string $model, string $slug, ?int $ignoreId): bool
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()
            : $model::query();

        return $query->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
