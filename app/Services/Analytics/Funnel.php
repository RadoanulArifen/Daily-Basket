<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Counting the steps between looking and buying.
 *
 * Four stages: a product page opened, something added to the bag, checkout reached, an order
 * paid for. Each visitor counts once per stage per day — otherwise somebody browsing fifty
 * sofas would look like fifty shoppers and the numbers would flatter you.
 *
 * Daily counters rather than one row per event: a busy shop would write millions of rows
 * nobody reads, and the questions worth asking are answered just as well by a tally.
 */
class Funnel
{
    public const STAGES = ['viewed', 'added', 'checkout', 'paid'];

    /** Count this visitor at this stage, once a day. */
    public function record(string $stage): void
    {
        if (! in_array($stage, self::STAGES, true)) {
            return;
        }

        $seen = "funnel:{$stage}:".now()->toDateString();

        if (session()->get($seen)) {
            return;
        }

        session()->put($seen, true);

        DB::table('funnel_counts')->upsert(
            [['day' => now()->toDateString(), 'stage' => $stage, 'count' => 1, 'created_at' => now(), 'updated_at' => now()]],
            ['day', 'stage'],
            ['count' => DB::raw('funnel_counts.count + 1'), 'updated_at' => now()],
        );
    }

    /** Somebody searched and found nothing. */
    public function missedSearch(string $term): void
    {
        $term = Str::lower(trim($term));

        if (mb_strlen($term) < 2 || mb_strlen($term) > 120) {
            return;
        }

        DB::table('search_misses')->upsert(
            [['term' => $term, 'count' => 1, 'last_searched_at' => now(), 'created_at' => now(), 'updated_at' => now()]],
            ['term'],
            ['count' => DB::raw('search_misses.count + 1'), 'last_searched_at' => now(), 'updated_at' => now()],
        );
    }

    /**
     * The funnel over a period, with the drop between each step.
     *
     * @return array{stages: list<array{stage: string, count: int, of_previous: ?float, of_top: ?float}>, days: int}
     */
    public function report(int $days = 30): array
    {
        $from = Carbon::today()->subDays(max(1, $days) - 1);

        $counts = DB::table('funnel_counts')
            ->where('day', '>=', $from->toDateString())
            ->selectRaw('stage, sum(count) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        $top = (int) ($counts['viewed'] ?? 0);
        $previous = null;
        $stages = [];

        foreach (self::STAGES as $stage) {
            $count = (int) ($counts[$stage] ?? 0);

            $stages[] = [
                'stage' => $stage,
                'count' => $count,
                // What share of the step before reached this one — where people are lost.
                'of_previous' => $previous === null || $previous === 0 ? null : round($count / $previous * 100, 1),
                'of_top' => $top === 0 ? null : round($count / $top * 100, 1),
            ];

            $previous = $count;
        }

        return ['stages' => $stages, 'days' => $days];
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public function missedSearches(int $limit = 20)
    {
        return DB::table('search_misses')
            ->orderByDesc('count')
            ->orderByDesc('last_searched_at')
            ->limit($limit)
            ->get();
    }
}
