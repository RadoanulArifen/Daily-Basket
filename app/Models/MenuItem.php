<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Localization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use Auditable, HasTranslations;

    public array $translatable = ['label'];

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'url',
        'open_in_new_tab',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /** The link in the visitor's current language. */
    public function href(): string
    {
        if (Str::startsWith($this->url, ['http://', 'https://', 'mailto:', 'tel:', '#'])) {
            return $this->url;
        }

        return app(Localization::class)->url($this->url);
    }
}
