<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    use Auditable, HasTranslations;

    public const PLACEMENTS = ['hero', 'promo'];

    /** Every place a banner can go: the base two, plus the active theme's own (ThemeSettings::BANNER_PLACEMENTS). */
    public static function placements(): array
    {
        return array_values(array_unique([...self::PLACEMENTS, ...(\App\Services\Theme\ThemeSettings::BANNER_PLACEMENTS[config('store.theme')] ?? [])]));
    }

    /** The name shown in the admin for a placement (a theme may name its own). */
    public static function placementLabel(string $placement): string
    {
        $theme = (string) config('store.theme');
        $key = "admin.banners.theme_placements.{$theme}.{$placement}";

        return $theme !== '' && \Illuminate\Support\Facades\Lang::has($key) ? __($key) : __('admin.banners.placements.'.$placement);
    }

    public array $translatable = ['title', 'text', 'button_label', 'eyebrow'];

    protected $fillable = ['placement', 'title', 'eyebrow', 'text', 'button_label', 'url', 'image_id', 'starts_at', 'ends_at', 'is_active', 'sort_order'];

    protected $attributes = [
        'eyebrow' => null,
        'text' => null,
        'button_label' => null,
        'url' => null,
        'image_id' => null,
        'starts_at' => null,
        'ends_at' => null,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /** Active and inside its start/end dates. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort_order')->orderByDesc('id');
    }

    /** Relative links ("/collections/sale") open in the visitor's language. */
    public function href(): ?string
    {
        if (! $this->url) {
            return null;
        }

        return str_starts_with($this->url, '/') ? store_url($this->url) : $this->url;
    }
}
