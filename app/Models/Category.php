<?php

namespace App\Models;

use App\Models\Concerns\HasRichText;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use Auditable, HasRichText, HasTranslations;

    /** How deep categories may nest: Living → Cushions → Floor cushions. */
    public const MAX_DEPTH = 3;

    public array $translatable = ['name', 'description', 'seo_title', 'seo_description'];

    /** Written in the admin's HTML editor; cleaned on save (HasRichText). */
    protected array $richText = ['description'];

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image_id', 'seo_title', 'seo_description', 'is_active', 'sort_order'];

    protected $attributes = [
        'parent_id' => null,
        'description' => null,
        'image_id' => null,
        'seo_title' => null,
        'seo_description' => null,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * All categories as a flat list in tree order, each with a "depth" attribute.
     * One query; fine for the few hundred categories a store has.
     *
     * @return Collection<int, Category>
     */
    public static function flatTree(?Builder $query = null): Collection
    {
        $all = ($query ?? static::query())->orderBy('sort_order')->orderBy('id')->get();
        $byParent = $all->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $result = collect();

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $result) {
            foreach ($byParent->get($parentId, []) as $category) {
                $category->setAttribute('depth', $depth);
                $result->push($category);
                $walk($category->id, $depth + 1);
            }
        };
        $walk(0, 0);

        // Categories whose parent was filtered out still appear, at the top level.
        $all->diff($result)->each(function (Category $c) use ($result) {
            $c->setAttribute('depth', 0);
            $result->push($c);
        });

        return $result;
    }

    /** IDs of this category and everything below it. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier) {
            $frontier = static::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /** 1 for a top-level category, 2 for its children, ... */
    public function level(): int
    {
        $level = 1;
        $parentId = $this->parent_id;

        while ($parentId && $level <= self::MAX_DEPTH + 1) {
            $parentId = static::query()->whereKey($parentId)->value('parent_id');
            $level++;
        }

        return $level;
    }
}
