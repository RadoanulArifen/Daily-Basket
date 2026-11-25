<?php

namespace App\Services\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The listing filters: brand, material and colour groups, price band, availability and
 * offers, each with a count.
 *
 * Material and colour groups are matched against a piece's materials line, its name and the
 * type it sits in — the same rule the design uses — so a store gets useful filters without
 * anyone having to tag every product by hand.
 */
class ProductFacets
{
    /** @var array<string, array{name: string, options: array<string, string[]>}> */
    public const GROUPS = [
        'dimensions' => ['name' => 'Dimensions', 'options' => [
            'Compact' => ['2-seat', 'side', 'stool', 'nightstand', 'counter', 'wall', 'pendant'],
            'Standard' => ['3-seat', 'dining chair', 'coffee', 'desk', 'armchair', 'lounge'],
            'Grand' => ['sectional', 'extending', 'sideboard', 'wardrobe', 'chandelier', 'bed', 'armoire'],
        ]],
        'frame' => ['name' => 'Frame', 'options' => [
            'Oak' => ['oak'], 'Walnut' => ['walnut'], 'Ash' => ['ash'], 'Steel' => ['steel'],
        ]],
        'finish' => ['name' => 'Finish', 'options' => [
            'Matte Black' => ['black', 'blackened'], 'Brushed Brass' => ['brass'],
            'Natural' => ['natural', 'oak', 'ash', 'travertine', 'marble', 'stone', 'cane'],
        ]],
        'upholstery' => ['name' => 'Upholstery', 'options' => [
            'Leather' => ['leather', 'aniline', 'cognac'], 'Bouclé' => ['bouclé', 'boucle'],
            'Linen & Wool' => ['linen', 'wool'], 'Velvet' => ['velvet'],
        ]],
        'configuration' => ['name' => 'Configuration', 'options' => [
            'Modular' => ['modular', 'sectional'], 'Drawers' => ['drawer'],
            'Doors & Cabinet' => ['door', 'cabinet', 'buffet', 'sideboard', 'wardrobe', 'armoire'],
        ]],
    ];

    /** @var array<string, array{name: string, swatch: string, keywords: string[]}> */
    public const COLOURS = [
        'wood' => ['name' => 'Natural Wood', 'swatch' => '#a9855f', 'keywords' => ['walnut', 'oak', 'ash', 'beech', 'wood', 'cane', 'rattan', 'ply']],
        'black' => ['name' => 'Black', 'swatch' => '#1d1d1d', 'keywords' => ['black', 'blackened', 'matte black']],
        'brass' => ['name' => 'Brass', 'swatch' => '#b99150', 'keywords' => ['brass']],
        'stone' => ['name' => 'Marble & Stone', 'swatch' => '#d9d5cf', 'keywords' => ['marble', 'travertine', 'stone', 'carrara', 'slate']],
        'leather' => ['name' => 'Leather', 'swatch' => '#7a4a2b', 'keywords' => ['leather', 'aniline', 'cognac']],
        'fabric' => ['name' => 'Fabric & Neutral', 'swatch' => '#e3ddd2', 'keywords' => ['bouclé', 'boucle', 'linen', 'wool', 'velvet', 'fabric', 'opal', 'glass', 'lacquer']],
    ];

    /** Price bands, in the store's own currency. */
    public const PRICE_BANDS = [
        'all' => ['label' => 'Any price', 'min' => null, 'max' => null],
        'lo' => ['label' => 'Under 5,000', 'min' => null, 'max' => 5000],
        'mid' => ['label' => '5,000 – 15,000', 'min' => 5000, 'max' => 15000],
        'hi' => ['label' => '15,000 +', 'min' => 15000, 'max' => null],
    ];

    /** Narrow a listing by the chosen groups, colours and availability. */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach (self::GROUPS as $id => $group) {
            foreach ((array) ($filters['attrs'][$id] ?? []) as $choice) {
                if (isset($group['options'][$choice])) {
                    $this->whereMentions($query, $group['options'][$choice]);
                }
            }
        }

        $colours = array_filter((array) ($filters['colours'] ?? []), fn ($c) => isset(self::COLOURS[$c]));
        if ($colours) {
            $query->where(function (Builder $outer) use ($colours) {
                foreach ($colours as $colour) {
                    $outer->orWhere(fn (Builder $q) => $this->whereMentions($q, self::COLOURS[$colour]['keywords']));
                }
            });
        }

        if ($cats = array_filter((array) ($filters['cats'] ?? []))) {
            $ids = \App\Models\Category::query()->whereIn('id', $cats)->get()
                ->flatMap(fn ($c) => $c->descendantIds())->unique()->all();
            $query->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids));
        }

        if ($brands = array_filter((array) ($filters['brands'] ?? []))) {
            $query->whereIn('products.brand_id', $brands);
        }

        if (($filters['availability'] ?? null) === 'made_to_order') {
            $query->where('products.made_to_order', true);
        }

        return $query;
    }

    /** Any of these words in the materials line, the name or the type. */
    private function whereMentions(Builder $query, array $keywords): Builder
    {
        return $query->where(function (Builder $outer) use ($keywords) {
            foreach ($keywords as $keyword) {
                $like = '%'.$keyword.'%';
                $outer->orWhere('products.name->'.app()->getLocale(), 'like', $like)
                    ->orWhere('products.short_description->'.app()->getLocale(), 'like', $like)
                    ->orWhereHas('categories', fn (Builder $c) => $c->where('categories.name->'.app()->getLocale(), 'like', $like));
            }
        });
    }

    /**
     * How many pieces each option would match within the current listing, worked out in one
     * pass over the listing's text.
     *
     * @return array{groups: array, colours: array, brands: array, availability: array, offers: int, total: int}
     */
    public function counts(Builder $scope): array
    {
        $rows = (clone $scope)->reorder()->with(['categories:id,name,parent_id'])
            ->get(['products.id', 'products.name', 'products.short_description', 'products.brand_id', 'products.made_to_order']);

        $text = $rows->mapWithKeys(fn ($p) => [$p->id => mb_strtolower(
            $p->getTranslation('name', app()->getLocale(), false).' '
            .$p->getTranslation('short_description', app()->getLocale(), false).' '
            .$p->categories->map(fn ($c) => $c->name)->implode(' ')
        )]);

        $matches = fn (array $keywords) => $rows->filter(fn ($p) => collect($keywords)
            ->contains(fn ($k) => str_contains($text[$p->id], mb_strtolower($k))))->count();

        return [
            'groups' => collect(self::GROUPS)->map(fn ($group) => [
                'name' => $group['name'],
                'options' => collect($group['options'])->map($matches)->all(),
            ])->all(),
            'colours' => collect(self::COLOURS)->map(fn ($colour) => $matches($colour['keywords']))->all(),
            'brands' => $rows->groupBy('brand_id')->map->count()->all(),
            // The design lists brands in the order they first appear in the results.
            'brand_order' => $rows->pluck('brand_id')->filter()->unique()->values()->all(),
            // A piece counts for its own type and for the category above it.
            'categories' => $rows->flatMap(fn ($p) => $p->categories
                ->flatMap(fn ($c) => array_filter([$c->id, $c->parent_id])))
                ->countBy()->all(),
            'availability' => [
                'made_to_order' => $rows->where('made_to_order', true)->count(),
                'all' => $rows->count(),
            ],
            'offers' => (clone $scope)->reorder()->whereHas('variants', fn (Builder $v) => $v
                ->where('is_active', true)->whereNotNull('compare_at_price')
                ->whereColumn('compare_at_price', '>', 'price'))->count(),
            'total' => $rows->count(),
        ];
    }

    /** Only options that match something, as in the design. */
    public function visible(array $options): Collection
    {
        return collect($options)->filter(fn ($count) => $count > 0);
    }
}
