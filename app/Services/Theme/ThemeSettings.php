<?php

namespace App\Services\Theme;

use App\Services\Settings\SettingsService;
use Throwable;

/**
 * Online Store → Themes → Customize. Settings are edited as a draft and go live on "Publish".
 * An admin who is previewing sees the draft on the real store; customers always see the
 * published version.
 */
class ThemeSettings
{
    public const SECTIONS = ['hero', 'ribbon', 'categories', 'featured', 'story', 'promos', 'new_in', 'sale', 'reviews', 'promises'];

    /**
     * Themes with their own homepage sections; others use SECTIONS. The design's own sections
     * come first; the rest are listed too, but start unticked (see OFF_BY_DEFAULT).
     */
    public const THEME_SECTIONS = [
        'furnivo' => ['hero', 'brands', 'categories', 'rails', 'ribbon', 'featured', 'new_in', 'sale', 'promos', 'story', 'reviews', 'promises'],
        'freshnest' => ['hero', 'categories', 'promos', 'picks', 'bundles', 'deal', 'banners', 'lists', 'rail', 'newsletter', 'trust'],
    ];

    /** Products per listing page, for themes whose grid is not the default 3 × 3. */
    public const PER_PAGE = [
        'freshnest' => 16,   // the design's 4 × 4 grid
    ];

    /**
     * Extra banner places a theme's homepage uses, besides "hero" and "promo"
     * (Content → Banners → Where). Names: admin.banners.theme_placements.<theme>.
     */
    public const BANNER_PLACEMENTS = [
        'freshnest' => ['hero_stat', 'weekend', 'side_promo', 'picks_promo', 'deal', 'promo_row', 'seasonal', 'newsletter', 'trust'],
    ];

    /** Sections a theme lists in Customize but leaves hidden until the owner ticks them. */
    public const OFF_BY_DEFAULT = [
        'furnivo' => ['ribbon', 'featured', 'new_in', 'sale', 'promos', 'story', 'reviews', 'promises'],
    ];

    /** @return list<string> section keys for the active theme, in default order */
    public static function keys(): array
    {
        return self::THEME_SECTIONS[config('store.theme')] ?? self::SECTIONS;
    }

    /** @return array<string, string> section key => name shown in Customize (a theme may rename one) */
    public static function labels(): array
    {
        $theme = (string) config('store.theme');

        return collect(self::keys())->mapWithKeys(fn ($k) => [$k => $theme !== '' && \Illuminate\Support\Facades\Lang::has("admin.online.theme_sections.{$theme}.{$k}")
            ? __("admin.online.theme_sections.{$theme}.{$k}")
            : __('admin.online.sections.'.$k)])->all();
    }

    public const FONTS = [
        'plex' => "'IBM Plex Sans', 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif",
        'system' => "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', 'IBM Plex Sans Arabic', sans-serif",
        'serif' => "Georgia, 'Times New Roman', 'IBM Plex Sans Arabic', serif",
        'rounded' => "ui-rounded, 'SF Pro Rounded', 'Nunito', 'IBM Plex Sans Arabic', system-ui, sans-serif",
    ];

    public const DEFAULTS = [
        'color_primary' => '#22262a',     // text, buttons
        'color_accent' => '#9b2c24',      // highlights, sale, links
        'color_background' => '#fcfbf9',  // page
        'color_surface' => '#f3eee6',     // soft panels
        'font' => 'plex',
        'sections' => null,               // null = all, in the default order
    ];

    public function __construct(private SettingsService $settings) {}

    public function previewing(): bool
    {
        try {
            return (bool) session('theme_preview') && auth('admin')->check();
        } catch (Throwable) {
            return false;
        }
    }

    public function published(): array
    {
        return array_merge(self::DEFAULTS, array_filter($this->settings->group('theme'), fn ($v) => $v !== null));
    }

    public function draft(): array
    {
        $draft = $this->settings->group('theme_draft');

        return $draft ? array_merge(self::DEFAULTS, array_filter($draft, fn ($v) => $v !== null)) : $this->published();
    }

    public function hasDraft(): bool
    {
        return (bool) $this->settings->group('theme_draft');
    }

    /** What the storefront uses right now. */
    public function current(): array
    {
        return $this->previewing() ? $this->draft() : $this->published();
    }

    /** @return list<array{key: string, visible: bool}> all sections, in display order */
    public function sections(?array $theme = null): array
    {
        $supported = self::keys();

        $saved = collect(($theme ?? $this->current())['sections'] ?? [])
            ->filter(fn ($s) => in_array($s['key'] ?? null, $supported, true))
            ->values();

        $off = self::OFF_BY_DEFAULT[config('store.theme')] ?? [];
        $missing = collect($supported)->diff($saved->pluck('key'))->map(fn ($k) => ['key' => $k, 'visible' => ! in_array($k, $off, true)]);

        return $saved->map(fn ($s) => ['key' => $s['key'], 'visible' => (bool) ($s['visible'] ?? true)])->concat($missing)->values()->all();
    }

    /** @return list<string> keys of the homepage sections to show, in order */
    public function visibleSections(): array
    {
        return collect($this->sections())->where('visible', true)->pluck('key')->all();
    }

    /** CSS variables for the storefront (printed after its stylesheet). */
    public function css(): string
    {
        $t = $this->current();
        $vars = [
            '--color-ink' => $t['color_primary'],
            '--color-brand-red' => $t['color_accent'],
            '--color-brand-red-dark' => self::shade($t['color_accent'], -0.18),
            '--color-paper' => $t['color_background'],
            '--color-wool-soft' => $t['color_surface'],
            '--color-wool' => self::shade($t['color_surface'], -0.06),
            '--font-sans' => self::FONTS[$t['font']] ?? self::FONTS['plex'],
        ];

        if ($vars === $this->defaultVars()) {
            return '';
        }

        return ':root{'.collect($vars)->map(fn ($v, $k) => $k.':'.$v)->implode(';').'}';
    }

    public function saveDraft(array $values): void
    {
        $this->settings->setMany('theme_draft', $values + ['saved_at' => now()->toIso8601String()]);
    }

    public function publish(): void
    {
        $draft = collect($this->draft())->only(array_keys(self::DEFAULTS))->all();
        $this->settings->setMany('theme', $draft);
        $this->discard();
    }

    public function discard(): void
    {
        $keys = array_keys($this->settings->group('theme_draft'));
        $this->settings->setMany('theme_draft', array_fill_keys($keys, null));
        \App\Models\Setting::query()->where('group', 'theme_draft')->delete();
        $this->settings->flush();
    }

    private function defaultVars(): array
    {
        $d = self::DEFAULTS;

        return [
            '--color-ink' => $d['color_primary'], '--color-brand-red' => $d['color_accent'], '--color-brand-red-dark' => self::shade($d['color_accent'], -0.18),
            '--color-paper' => $d['color_background'], '--color-wool-soft' => $d['color_surface'], '--color-wool' => self::shade($d['color_surface'], -0.06),
            '--font-sans' => self::FONTS['plex'],
        ];
    }

    /** Lighten (+) or darken (−) a #rrggbb colour. */
    public static function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map(fn ($i) => hexdec(substr($hex, $i, 2)), [0, 2, 4]);
        $rgb = array_map(fn ($c) => (int) round($amount < 0 ? $c * (1 + $amount) : $c + (255 - $c) * $amount), $rgb);

        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}
