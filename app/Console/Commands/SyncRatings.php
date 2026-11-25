<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Console\Command;

/**
 * Put every product's score and review count back in step with its approved reviews.
 *
 * Useful after importing products or seeding demo data, where the stored numbers can end up
 * claiming more reviews than the page can actually show.
 */
class SyncRatings extends Command
{
    protected $signature = 'store:sync-ratings';

    protected $description = 'Recalculate product ratings from their approved reviews';

    public function handle(): int
    {
        $fixed = 0;

        Product::withTrashed()->select(['id', 'rating_count', 'rating_average'])->chunkById(200, function ($products) use (&$fixed) {
            foreach ($products as $product) {
                $was = $product->rating_count;
                ProductReview::refreshProductRating($product->id);

                if ($was !== (int) $product->fresh()->rating_count) {
                    $fixed++;
                }
            }
        });

        $this->info($fixed > 0
            ? $fixed.' product(s) had the wrong review count; all products are now in step.'
            : 'Every product already matched its reviews.');

        return self::SUCCESS;
    }
}
