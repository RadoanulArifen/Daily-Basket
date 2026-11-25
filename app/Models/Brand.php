<?php

namespace App\Models;

use App\Models\Concerns\HasRichText;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Brand extends Model
{
    use Auditable, HasRichText, HasTranslations;

    public array $translatable = ['name', 'origin', 'description'];

    /** Written in the admin's HTML editor; cleaned on save (HasRichText). */
    protected array $richText = ['description'];

    protected $fillable = ['name', 'slug', 'origin', 'description', 'logo', 'logo_small', 'is_active', 'position'];

    protected $attributes = ['origin' => null, 'description' => null, 'logo' => null, 'logo_small' => null, 'is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /** A logo path under public/ ("themes/…") or in the uploads disk. */
    public function logoUrl(bool $small = false): ?string
    {
        $path = $small ? ($this->logo_small ?: $this->logo) : $this->logo;
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'themes/') ? asset($path) : \Illuminate\Support\Facades\Storage::disk(config('store.uploads.disk'))->url($path);
    }
}
