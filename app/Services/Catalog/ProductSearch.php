<?php

// SECURITY: every search term is passed as a bound parameter (?), never concatenated into SQL —
// LIKE clauses go through App\Support\Search::like(), relevance ordering uses orderByRaw(sql, [$bindings]).

namespace App\Services\Catalog;

use App\Models\Product;
use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finding things in the catalogue.
 *
 * A shopper types "sofa bed", "3 seater couch" or "chesterfeild". A plain LIKE on the whole
 * phrase finds none of those, so this does three things instead:
 *
 *  1. Splits the phrase into words and requires every word to appear somewhere — the name,
 *     the description, the material, the brand or a SKU. "sofa bed" therefore matches a
 *     "Bed-sized sofa" as well as a "Sofa bed".
 *  2. Expands a small list of synonyms, so "couch" finds sofas and "lamp" finds lighting.
 *  3. When that finds nothing, offers the closest catalogue word as a suggestion, which is
 *     how a misspelling gets rescued.
 *
 * Results are ordered by where the match was found: a word in the name beats a word buried
 * in a description.
 */
class ProductSearch
{
    /** Words shoppers use for the same thing. Both directions are applied. */
    private const SYNONYMS = [
        'sofa' => ['couch', 'settee', 'lounge'],
        'armchair' => ['chair', 'accent chair'],
        'lamp' => ['light', 'lighting'],
        'pendant' => ['ceiling light', 'hanging light'],
        'rug' => ['carpet'],
        'sideboard' => ['buffet', 'credenza'],
        'wardrobe' => ['closet', 'armoire'],
        'nightstand' => ['bedside table', 'side table'],
        'desk' => ['workstation'],
        'bookcase' => ['shelving', 'shelf', 'bookshelf'],
        'dining table' => ['kitchen table'],
        'ottoman' => ['footstool', 'pouffe'],
    ];

    /** The columns a word may match, best first — the order sets the ranking. */
    private const FIELDS = ['name->en', 'name->ar', 'short_description->en', 'short_description->ar', 'description->en', 'description->ar', 'brand'];

    /** Words too common to narrow anything down. */
    private const NOISE = ['the', 'and', 'for', 'with', 'a', 'an', 'of', 'in', 'my', 'our'];

    /**
     * Every word must appear somewhere; each word may appear in any field.
     */
    public function apply(Builder $query, string $term): Builder
    {
        $words = $this->words($term);

        if ($words->isEmpty()) {
            return $query;
        }

        foreach ($words as $word) {
            $forms = $this->withSynonyms($word);

            $query->where(function (Builder $outer) use ($forms) {
                foreach ($forms as $form) {
                    $outer->orWhere(function (Builder $q) use ($form) {
                        foreach (self::FIELDS as $field) {
                            Search::like($q, $field, $form, or: true);
                        }

                        $q->orWhereHas('variants', fn (Builder $v) => Search::like($v->where('is_active', true), 'sku', $form));
                    });
                }
            });
        }

        // A hit in the name is worth more than one in a description.
        $first = $words->first();

        // Parameter-bound: the search word is passed as a binding (?), never concatenated.
        return $query->orderByRaw(
            'case when '.$query->getQuery()->getGrammar()->wrap('name->en').' like ? then 0 else 1 end',
            ['%'.$first.'%'],
        );
    }

    /**
     * The closest catalogue word to what was typed, when a search found nothing.
     *
     * Compared against the words that actually appear in product names, so the suggestion is
     * always something that will return results.
     */
    public function suggestion(string $term): ?string
    {
        $typed = $this->words($term)->first();

        if (! $typed || mb_strlen($typed) < 4) {
            return null;
        }

        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($this->catalogueWords() as $word) {
            $distance = levenshtein($typed, $word);

            // Allow one mistake per four characters, and never more than three.
            if ($distance > 0 && $distance <= min(3, intdiv(mb_strlen($typed), 4) + 1) && $distance < $bestDistance) {
                $best = $word;
                $bestDistance = $distance;
            }
        }

        return $best;
    }

    /** @return Collection<int, string> */
    private function words(string $term): Collection
    {
        return Str::of(mb_strtolower(trim($term)))
            ->replaceMatches('/[^\p{L}\p{N}\s\-]+/u', ' ')
            ->explode(' ')
            ->map(fn ($word) => trim($word))
            ->filter(fn ($word) => mb_strlen($word) >= 2 && ! in_array($word, self::NOISE, true))
            ->take(6)
            ->values();
    }

    /** @return array<int, string> */
    private function withSynonyms(string $word): array
    {
        $forms = [$word];

        foreach (self::SYNONYMS as $canonical => $others) {
            if ($word === $canonical) {
                $forms = array_merge($forms, $others);
            } elseif (in_array($word, $others, true)) {
                $forms[] = $canonical;
            }
        }

        return array_values(array_unique($forms));
    }

    /** Every distinct word used in a live product name. @return array<int, string> */
    private function catalogueWords(): array
    {
        return cache()->remember('catalogue-words', now()->addHour(), function () {
            $words = [];

            Product::query()->where('status', 'active')->pluck('name')->each(function ($name) use (&$words) {
                $text = is_array($name) ? implode(' ', $name) : (string) $name;

                foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [] as $word) {
                    if (mb_strlen($word) >= 4) {
                        $words[$word] = true;
                    }
                }
            });

            return array_keys($words);
        });
    }
}
