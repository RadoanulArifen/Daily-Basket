<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * An uploaded image, stored as WebP in several sizes.
 * Use ->url('thumb' | 'medium' | 'large').
 */
class Media extends Model
{
    use Auditable, HasTranslations;

    /** Longest side in pixels for each stored size. */
    public const SIZES = ['thumb' => 320, 'medium' => 800, 'large' => 1600];

    public array $translatable = ['alt'];

    protected $fillable = ['disk', 'collection', 'directory', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt', 'sizes'];

    protected $attributes = [
        'alt' => null,
        'collection' => 'library',
    ];

    protected function casts(): array
    {
        return [
            'sizes' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position');
    }

    public function path(string $size = 'medium'): ?string
    {
        $sizes = $this->sizes ?? [];
        // Small uploads don't get every size; fall back to the biggest one stored.
        $file = $sizes[$size] ?? (end($sizes) ?: null);

        return $file ? $this->directory.'/'.$file : null;
    }

    public function url(string $size = 'medium'): ?string
    {
        $path = $this->path($size);

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }

    /** A full URL, for places outside the page: search engines, feeds, exports, emails. */
    public function absoluteUrl(string $size = 'medium'): ?string
    {
        $url = $this->url($size);

        return $url === null || preg_match('#^(https?:)?//#', $url) ? $url : url($url);
    }

    /** srcset for responsive <img> tags. */
    public function srcset(): string
    {
        return collect(self::SIZES)
            ->filter(fn ($px, $size) => isset($this->sizes[$size]))
            ->map(fn ($px, $size) => $this->url($size).' '.min($px, max($this->width, 1)).'w')
            ->implode(', ');
    }

    /** Data the admin JavaScript needs. */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'thumb' => $this->url('thumb'),
            'url' => $this->url('medium'),
            'name' => $this->original_name,
            'alt' => $this->getTranslation('alt', 'en', false),
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
