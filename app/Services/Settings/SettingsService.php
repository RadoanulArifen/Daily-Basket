<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Store settings, addressed as "group.key" (e.g. "general.store_name").
 *
 * All settings are loaded with one query and cached until something changes.
 * Translatable values are stored as {"en": "...", "ar": "..."} and read with trans().
 */
class SettingsService
{
    private const CACHE_KEY = 'store.settings';

    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /** A translatable setting in the current (or given) language, falling back to the default language. */
    public function trans(string $key, ?string $locale = null, ?string $default = null): ?string
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return $value ?? $default;
        }

        $locale ??= app()->getLocale();

        return filled($value[$locale] ?? null)
            ? $value[$locale]
            : ($value[config('store.default_locale')] ?? $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, array<string, mixed>> */
    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    /** @param array<string, mixed> $values keys relative to the group */
    public function setMany(string $group, array $values): void
    {
        DB::transaction(function () use ($group, $values) {
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        });

        $this->flush();
    }

    /** The low-stock warning level: Settings → Inventory, else config('store.low_stock_threshold'). */
    public function lowStockThreshold(): int
    {
        return (int) $this->get('inventory.low_stock_threshold', (int) config('store.low_stock_threshold', 5));
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $name] = explode('.', $key, 2);

        $this->setMany($group, [$name => $value]);
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
                return Setting::query()
                    ->get(['group', 'key', 'value'])
                    ->groupBy('group')
                    ->map(fn ($rows) => $rows->pluck('value', 'key')->all())
                    ->all();
            });
        } catch (Throwable $e) {
            // Before migrations run (fresh install) the table does not exist yet.
            if (! Schema::hasTable('settings')) {
                return $this->loaded = [];
            }

            throw $e;
        }
    }
}
